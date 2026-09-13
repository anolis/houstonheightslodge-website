#!/usr/bin/env bash
# Requires only Bash, Git, tar, and Docker. No host PHP, Composer, or Node.
set -euo pipefail
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$root"
production=false
artifact=
identity=${DEPLOY_IDENTITY:-${HOME}/.ssh/houstonheightslodge225.pem}
prod_host=${PROD_HOST:-100.97.10.38}
prod_user=${PROD_USER:-admin}
while (($#)); do
    case "$1" in
        --check) production=false; shift ;;
        --production) production=true; shift ;;
        --artifact) artifact=$2; shift 2 ;;
        --identity) identity=$2; shift 2 ;;
        --host) prod_host=$2; shift 2 ;;
        --user) prod_user=$2; shift 2 ;;
        --help)
            echo 'Usage: tools/deploy.sh [--check|--production] [--artifact DIR] [--identity FILE] [--host HOST] [--user USER]'
            echo 'Default: build, test, and smoke-check locally. --production deploys a clean checkout of current origin/main.'
            exit 0 ;;
        *) echo "Unknown argument: $1" >&2; exit 2 ;;
    esac
done
sha=$(git rev-parse HEAD)
if $production; then
    test -z "$(git status --porcelain)" || { echo 'Production requires a clean checkout.' >&2; exit 1; }
    git fetch origin main
    test "$sha" = "$(git rev-parse origin/main)" || { echo 'Check out the current origin/main before deploying.' >&2; exit 1; }
    test -r "$identity" || { echo "Cannot read SSH identity: $identity" >&2; exit 1; }
fi
work=$(mktemp -d)
tag="lodge-website-${sha:0:12}-$$"
staging_id=
client_id=
cleanup() {
    if [[ -n "$staging_id" ]]; then docker rm -f "$staging_id" >/dev/null 2>&1 || true; fi
    if [[ -n "$client_id" ]]; then docker rm -f "$client_id" >/dev/null 2>&1 || true; fi
    docker image rm "$tag:test" "$tag:staging" "$tag:client" >/dev/null 2>&1 || true
    rm -rf -- "$work"
}
trap cleanup EXIT
context=$root
if $production || [[ -n "$artifact" ]]; then
    mkdir "$work/source"
    git archive "$sha" | tar -x -C "$work/source"
    context=$work/source
fi
frontend='frontend-built'
if [[ -n "$artifact" ]]; then
    test -s "$artifact/manifest.json"
    mkdir -p "$context/public/build"
    cp -a "$artifact/." "$context/public/build/"
    frontend='frontend-prebuilt'
fi
docker build --target test -f "$context/docker/Dockerfile" -t "$tag:test" "$context"
docker run --rm "$tag:test"
docker build --target staging --build-arg "FRONTEND_SOURCE=$frontend" \
    -f "$context/docker/Dockerfile" -t "$tag:staging" "$context"
staging_id=$(docker run -d "$tag:staging")
ready=false
for _ in {1..60}; do
    if docker exec "$staging_id" curl --fail --silent --max-time 5 http://127.0.0.1/up >/dev/null; then ready=true; break; fi
    sleep 1
done
if ! $ready; then docker logs "$staging_id"; exit 1; fi
for path in / /about /downloads /NNO2026 /up /res/img/nno-2026.png; do
    docker exec "$staging_id" curl --fail --show-error --silent --max-time 20 --output /dev/null "http://127.0.0.1$path"
done
docker cp "$staging_id:/var/www/html/public/build" "$work/build" >/dev/null
test -s "$work/build/manifest.json"
echo "Docker checks passed: $sha"
if ! $production; then exit 0; fi
tar -czf "$work/build.tgz" -C "$work/build" .
docker build --target deploy-client -f "$context/docker/Dockerfile" -t "$tag:client" "$context"
client_id=$(docker create -e "DEPLOY_SHA=$sha" -e "PROD_HOST=$prod_host" -e "PROD_USER=$prod_user" "$tag:client")
# docker cp works with Docker Desktop and with a runner using the host socket;
# no assumptions about bind-mount paths on the Docker daemon's host.
docker cp "$work/build.tgz" "$client_id:/run/deploy/build.tgz"
docker cp -L "$identity" "$client_id:/run/deploy/key"
docker start -a "$client_id"
test "$(docker inspect --format '{{.State.ExitCode}}' "$client_id")" = 0
