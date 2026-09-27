# Flussonic module for XC_VM

## Install

Extract the release on the XC_VM MAIN server and run:

```bash
sudo ./install.sh
```

Defaults can be overridden with `XCVM_HOME`, `XCVM_MODULES_DIR`, `XCVM_PHP_BIN`, and
`XCVM_OWNER`. The installer validates every PHP file and atomically replaces an
existing copy after creating a timestamped backup.

## Test the Flussonic API

Credentials are read from environment variables so they do not appear in the
process list:

```bash
sudo -u xc_vm env \
  FLUSSONIC_URL='https://media.example.com' \
  FLUSSONIC_USER='api-user' \
  FLUSSONIC_PASSWORD='secret' \
  /home/xc_vm/Modules/flussonic_1f4a9/bin/flussonic-api.php info
```

Commands: `info`, `streams [limit] [offset]`, and `stream <name>`. TLS validation
is enabled. For a temporary lab with a self-signed certificate only, set
`FLUSSONIC_VERIFY_TLS=0`.

## Uninstall

From the extracted release directory:

```bash
sudo ./uninstall.sh --yes
```

Uninstall moves the module to a timestamped `.removed.*` directory rather than
deleting it. Remove that backup manually after verification.
