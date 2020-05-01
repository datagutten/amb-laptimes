CREATE TABLE `transponders` (
  `transponder_id` int(11) NOT NULL,
  `transponder_name` varchar(45) DEFAULT NULL COMMENT 'Transponder name',
  `nickname` varchar(255) DEFAULT NULL COMMENT 'User nick name',
  `avatar` text DEFAULT NULL COMMENT 'Base64 encoded profile picture',
  PRIMARY KEY (`transponder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
