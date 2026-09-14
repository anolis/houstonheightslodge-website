#!/usr/bin/env bash
set -euo pipefail
: "${DEPLOY_SHA:?}" "${PROD_HOST:?}" "${PROD_USER:?}"
[[ "$DEPLOY_SHA" =~ ^[0-9a-f]{40}$ ]] || exit 2
[[ "$PROD_HOST" =~ ^[a-zA-Z0-9][a-zA-Z0-9.-]*$ ]] || exit 2
[[ "$PROD_USER" =~ ^[a-zA-Z0-9_][a-zA-Z0-9_-]*$ ]] || exit 2
chmod 600 /run/deploy/key
ssh_options=(-i /run/deploy/key -o BatchMode=yes -o IdentitiesOnly=yes
    -o StrictHostKeyChecking=yes -o UserKnownHostsFile=/opt/deploy/known_hosts
    -o ConnectTimeout=15 -o ServerAliveInterval=15 -o ServerAliveCountMax=4)
target="$PROD_USER@$PROD_HOST"
release_tmp=$(ssh "${ssh_options[@]}" "$target" 'mktemp -d /tmp/lodge-deploy.XXXXXXXX')
[[ "$release_tmp" =~ ^/tmp/lodge-deploy\.[a-zA-Z0-9_]+$ ]] || exit 2
cleanup() {
    # Validated above; these arguments intentionally expand on the client.
    # shellcheck disable=SC2029
    ssh "${ssh_options[@]}" "$target" "rm -rf -- '$release_tmp'" || true
}
trap cleanup EXIT
scp "${ssh_options[@]}" /run/deploy/build.tgz "$target:$release_tmp/build.tgz"
# shellcheck disable=SC2029
ssh "${ssh_options[@]}" "$target" "bash -s -- '$DEPLOY_SHA' '$release_tmp'" < /opt/deploy/remote.sh
for path in / /about /downloads /NNO2026 /up; do
    curl --fail --show-error --silent --max-time 20 --retry 5 --retry-all-errors \
        --output /dev/null "https://houstonheightslodge225.com$path"
done
echo "Production deployment verified: $DEPLOY_SHA"
