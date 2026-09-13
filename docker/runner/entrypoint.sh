#!/usr/bin/env bash
set -euo pipefail
cd /runner
if [[ ! -f ./config.sh ]]; then cp -a /opt/actions-runner/. .; fi
if [[ ${1:-} == register ]]; then
    [[ ! -f .runner ]] || { echo 'Runner volume is already registered.' >&2; exit 1; }
    read -r registration_token
    ./config.sh --unattended --url https://github.com/anolis/houstonheightslodge-website \
        --token "$registration_token" --name "$2" --labels website-docker \
        --work _work
    exit 0
fi
test -f .runner || { echo 'Register the runner first with tools/install-runner.sh.' >&2; exit 1; }
# Auto-updates persist in the named volume, including across container restarts.
exec ./run.sh
