#!/usr/bin/env bash
set -euo pipefail
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
name=${1:-bb-website-docker}
[[ "$name" =~ ^[a-zA-Z0-9][a-zA-Z0-9_-]+$ ]] || exit 2
case $(uname -m) in
    x86_64) arch=x64 ;;
    aarch64|arm64) arch=arm64 ;;
    *) echo 'Supported runner hosts: x86_64 and ARM64 Linux.' >&2; exit 2 ;;
esac
[[ $(uname -s) == Linux ]] || { echo 'The always-on runner installer requires Linux. Portable deployments also work with Docker Desktop.' >&2; exit 2; }
version=$(gh api repos/actions/runner/releases/latest --jq '.tag_name')
version=${version#v}
digest=$(gh api repos/actions/runner/releases/latest --jq ".assets[] | select(.name == \"actions-runner-linux-$arch-$version.tar.gz\") | .digest")
digest=${digest#sha256:}
[[ "$digest" =~ ^[0-9a-f]{64}$ ]] || { echo 'Runner release is missing a SHA-256 digest.' >&2; exit 1; }
image="lodge-actions-runner:$version"
docker build --build-arg "RUNNER_VERSION=$version" --build-arg "RUNNER_ARCH=$arch" \
    --build-arg "RUNNER_SHA256=$digest" -t "$image" "$root/docker/runner"
volume="$name-state"
docker volume create "$volume" >/dev/null
if ! docker run --rm --entrypoint test -v "$volume:/runner" "$image" -f /runner/.runner; then
    gh api --method POST repos/anolis/houstonheightslodge-website/actions/runners/registration-token --jq .token |
        docker run --rm -i -v "$volume:/runner" "$image" register "$name"
fi
if docker container inspect "$name" >/dev/null 2>&1; then
    echo "Runner container already exists: $name. Leaving it running."
else
    docker run -d --name "$name" --restart unless-stopped --network host \
        -v "$volume:/runner" -v /var/run/docker.sock:/var/run/docker.sock "$image"
fi
echo "Runner installed: $name (label: website-docker)."
