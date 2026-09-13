FROM docker:27-cli AS docker-cli
FROM ubuntu:24.04
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl git jq openssh-client tar unzip \
    libicu74 libssl3 libkrb5-3 zlib1g liblttng-ust1 libcurl4 \
    && rm -rf /var/lib/apt/lists/*
COPY --from=docker-cli /usr/local/bin/docker /usr/local/bin/docker
COPY --from=docker-cli /usr/local/libexec/docker/cli-plugins/ /usr/local/libexec/docker/cli-plugins/
ARG RUNNER_VERSION
ARG RUNNER_ARCH
ARG RUNNER_SHA256
RUN test -n "$RUNNER_VERSION" && test -n "$RUNNER_SHA256" \
    && mkdir /opt/actions-runner /runner \
    && curl -fSL "https://github.com/actions/runner/releases/download/v${RUNNER_VERSION}/actions-runner-linux-${RUNNER_ARCH}-${RUNNER_VERSION}.tar.gz" -o /tmp/runner.tgz \
    && echo "$RUNNER_SHA256  /tmp/runner.tgz" | sha256sum -c - \
    && tar -xzf /tmp/runner.tgz -C /opt/actions-runner \
    && rm /tmp/runner.tgz
COPY entrypoint.sh /usr/local/bin/runner-entrypoint
ENV RUNNER_ALLOW_RUNASROOT=1
WORKDIR /runner
ENTRYPOINT ["bash", "/usr/local/bin/runner-entrypoint"]
