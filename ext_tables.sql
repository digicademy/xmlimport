#
# TABLE STRUCTURE FOR TABLE 'tx_xmlimport_recordcache'
#
CREATE TABLE tx_xmlimport_recordcache (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(250) DEFAULT '' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	content mediumblob,
	lifetime int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (id),
	KEY cache_id (identifier)
) ENGINE=InnoDB;
 
#
# TABLE STRUCTURE FOR TABLE 'tx_xmlimport_recordcache_tags'
#
CREATE TABLE tx_xmlimport_recordcache_tags (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(250) DEFAULT '' NOT NULL,
	tag varchar(250) DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	KEY cache_id (identifier),
	KEY cache_tag (tag)
) ENGINE=InnoDB;

#
# Table structure for table 'sys_registry'
#
CREATE TABLE sys_registry (
	entry_value mediumblob,
);