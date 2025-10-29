ARG BASE_IMAGE
FROM $BASE_IMAGE

# Install Node.js 22 (latest LTS)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
  && apt-get install -y nodejs \
  && npm install -g npm@latest
