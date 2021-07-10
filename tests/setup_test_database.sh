#!/usr/bin/env sh
cp "$GITHUB_WORKSPACE/tests/test_config_db.php" "$GITHUB_WORKSPACE/tests/config_db.php"
ls "$GITHUB_WORKSPACE/tests/config_db.php"
mysql passings_test <"$GITHUB_WORKSPACE/tests/setup.sql"
mysql passings_test <"$GITHUB_WORKSPACE/tests/test_data/passings.sql"
mysql passings_test <"$GITHUB_WORKSPACE/tests/test_data/transponders.sql"
mysql passings_test <"$GITHUB_WORKSPACE/tests/test_data/transponder_records.sql"
