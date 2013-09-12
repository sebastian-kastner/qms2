-- phpMyAdmin SQL Dump
-- version 2.11.3deb1ubuntu1.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 05. März 2010 um 13:33
-- Server Version: 5.0.51
-- PHP-Version: 5.2.4-2ubuntu5.10

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `qms2`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `process`
--

CREATE TABLE `process` (
  `process_id` int(5) NOT NULL auto_increment,
  `name` varchar(60) character set utf8 NOT NULL,
  `notation` varchar(10) character set utf8 NOT NULL,
  `lft` int(6) NOT NULL,
  `rgt` int(6) NOT NULL,
  `process_type_id` int(4) NOT NULL,
  `date_created` int(12) default NULL,
  `created_on` int(12) NOT NULL,
  `date_updated` int(12) default NULL,
  `updated_on` int(12) NOT NULL,
  `is_active` enum('0','1') character set utf8 NOT NULL,
  PRIMARY KEY  (`process_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=91 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `process_attributes`
--

CREATE TABLE `process_attributes` (
  `process_attribute_id` int(4) NOT NULL auto_increment,
  `name` varchar(60) NOT NULL,
  `position` int(2) NOT NULL,
  `form_type` set('textarea','input') NOT NULL,
  `form_size` int(2) NOT NULL,
  `created_by` int(12) default NULL,
  `date_created` int(12) default NULL,
  `updated_by` varchar(30) default NULL,
  `date_updated` int(12) default NULL,
  `is_active` enum('0','1') NOT NULL,
  PRIMARY KEY  (`process_attribute_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `process_has_attribute`
--

CREATE TABLE `process_has_attribute` (
  `process_attribute_id` int(4) NOT NULL auto_increment,
  `process_id` int(5) NOT NULL,
  `attribute_value` text NOT NULL,
  `created_by` varchar(60) NOT NULL,
  `date_created` int(12) default NULL,
  `updated_by` varchar(60) NOT NULL,
  `date_updated` int(12) default NULL,
  `is_active` enum('0','1') NOT NULL,
  UNIQUE KEY `process_attribute_id` (`process_attribute_id`,`process_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `process_has_process`
--

CREATE TABLE `process_has_process` (
  `from_process_id` int(5) NOT NULL,
  `to_process_id` int(5) NOT NULL,
  `process_interrelation_id` int(5) NOT NULL,
  `created_by` varchar(30) NOT NULL,
  `date_created` int(12) default NULL,
  `updated_by` varchar(30) NOT NULL,
  `date_updated` int(12) default NULL,
  `is_active` tinyint(1) NOT NULL,
  UNIQUE KEY `process_id` (`from_process_id`,`to_process_id`, `process_interrelation_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `process_types`
--

CREATE TABLE `process_types` (
  `process_type_id` int(4) NOT NULL auto_increment,
  `shortcut` varchar(3) NOT NULL,
  `name` varchar(60) NOT NULL,
  `position` int(2) NOT NULL,
  `created_by` varchar(30) NOT NULL,
  `date_created` int(12) default NULL,
  `updated_by` varchar(30) NOT NULL,
  `date_updated` int(12) default NULL,
  `is_active` enum('0','1') NOT NULL,
  PRIMARY KEY  (`process_type_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `process_interrelations`
--


CREATE  TABLE IF NOT EXISTS `mydb`.`process_interrelations` (
  `process_interrelation_id` INT NOT NULL AUTO_INCREMENT ,
  `description` VARCHAR(150) NULL ,
  `created_by` VARCHAR(30) NULL ,
  `created_on` INT(12) NULL ,
  `updated_by` VARCHAR(30) NULL ,
  `updated_on` INT(12) NULL ,
  `is_active` TINYINT(1) NULL ,
  PRIMARY KEY (`process_interrelation_id`) )
ENGINE = MyISAM
