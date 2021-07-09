#!/usr/bin/env sh
cp tests/test_config_db.php tests/config_db.php
mysql passings_test < tests/setup.sql
mysql passings_test <tests/test_data/passings.sql
mysql passings_test <tests/test_data/transponders.sql
mysql passings_test <tests/test_data/transponder_records.sql
