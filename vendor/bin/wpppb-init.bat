@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../jdgrimes/wpppb/bin/wpppb-init
bash "%BIN_TARGET%" %*
