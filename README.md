# letsencrypt.sh-transip-api-dns-validator
DNS Validator hook for letsencrypt.sh using the TransIP API

Requirements/Preparations
  - TransIP dns zone
  - TransIP API credentials and whitelist access
  - Download the TransIP API, unpack in subdir 'Transip'
  - Paste your api private key in Transip/ApiSettings.php
  - Update/Edit the regex 'pattern' in hook-dns-transip-api.php to match your domain(s)

Testing:
  letsencrypt.sh --cron --domain some.test.domain --hook hook-dns-transip-api.php --challenge dns-01

It's recommended to test against the staging-api for Letsencrypt, to do so, set
  CA="https://acme-staging.api.letsencrypt.org/directory"
in your letsencrypt.sh/config.sh or specify an alternate config-file with --config
