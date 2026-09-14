"""Exercise production deploy safety gates without contacting production."""
import fcntl
import io
import json
import os
from pathlib import Path
import subprocess
import tarfile
import tempfile
import unittest

REMOTE = Path(__file__).resolve().parents[1] / "docker/deploy/remote.sh"
SHA = "a" * 40


class DeploySafetyTest(unittest.TestCase):
    def setUp(self):
        self.work = tempfile.TemporaryDirectory()
        self.release = tempfile.TemporaryDirectory(prefix="lodge-deploy.", dir="/tmp")
        self.addCleanup(self.work.cleanup)
        self.addCleanup(self.release.cleanup)
        root = Path(self.work.name)
        (root / "storage").mkdir()
        (root / ".git").mkdir()
        (root / "bin").mkdir()
        self.log = root / "commands.jsonl"
        fake = root / "bin/fake"
        fake.write_text('''#!/usr/bin/env python3
import json, os, sys
from pathlib import Path
name = Path(sys.argv[0]).name
args = sys.argv[1:]
with open(os.environ["TEST_LOG"], "a") as log:
    log.write(json.dumps([name, *args]) + "\\n")
if name == "git":
    if args == ["branch", "--show-current"]: print("main")
    if args == ["status", "--porcelain"]: print(os.environ.get("TEST_DIRTY", ""), end="")
    if args == ["rev-parse", "origin/main"]: print(os.environ["TEST_REMOTE_SHA"])
    if args == ["rev-parse", "--git-path", "lodge-deploy.lock"]: print(".git/lodge-deploy.lock")
if name == "composer" and os.environ.get("TEST_FAIL_COMPOSER"): sys.exit(1)
''')
        fake.chmod(0o755)
        for name in ("git", "php", "composer", "rsync"):
            (root / "bin" / name).symlink_to(fake)
        self.env = dict(os.environ, PATH=f"{root}/bin:{os.environ['PATH']}",
                        DEPLOY_ROOT=str(root), TEST_LOG=str(self.log),
                        TEST_REMOTE_SHA=SHA, DEPLOY_LOCK_TIMEOUT="1")
        with tarfile.open(Path(self.release.name) / "build.tgz", "w:gz") as archive:
            info = tarfile.TarInfo("manifest.json")
            info.size = 2
            archive.addfile(info, io.BytesIO(b"{}"))

    def run_deploy(self):
        return subprocess.run(["bash", str(REMOTE), SHA, self.release.name],
                              env=self.env, capture_output=True, text=True, timeout=5)

    def commands(self):
        return [json.loads(line) for line in self.log.read_text().splitlines()] if self.log.exists() else []

    def test_success_deploys_exact_commit_and_leaves_maintenance(self):
        result = self.run_deploy()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn(["git", "merge", "--ff-only", SHA], self.commands())
        self.assertEqual(self.commands()[-1], ["php", "artisan", "up"])

    def test_stale_commit_is_rejected_before_maintenance(self):
        self.env["TEST_REMOTE_SHA"] = "b" * 40
        self.assertNotEqual(self.run_deploy().returncode, 0)
        self.assertFalse(any(cmd[0] in ("php", "composer", "rsync") for cmd in self.commands()))

    def test_dirty_production_is_rejected_before_maintenance(self):
        self.env["TEST_DIRTY"] = " M routes/web.php"
        self.assertNotEqual(self.run_deploy().returncode, 0)
        self.assertFalse(any(cmd[0] in ("php", "composer", "rsync") for cmd in self.commands()))

    def test_dependency_failure_exits_maintenance_and_fails(self):
        self.env["TEST_FAIL_COMPOSER"] = "1"
        self.assertNotEqual(self.run_deploy().returncode, 0)
        self.assertEqual(self.commands()[-1], ["php", "artisan", "up"])
        self.assertFalse(any(cmd[0] == "rsync" for cmd in self.commands()))

    def test_concurrent_deploy_cannot_modify_production(self):
        with open(Path(self.work.name) / ".git/lodge-deploy.lock", "w") as lock:
            fcntl.flock(lock, fcntl.LOCK_EX)
            self.assertNotEqual(self.run_deploy().returncode, 0)
        self.assertEqual(self.commands(), [["git", "rev-parse", "--git-path", "lodge-deploy.lock"]])


if __name__ == "__main__":
    unittest.main()
