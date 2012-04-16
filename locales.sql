-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 17, 2012 at 01:26 AM
-- Server version: 5.5.16
-- PHP Version: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `top5news`
--

-- --------------------------------------------------------

--
-- Table structure for table `locales`
--

CREATE TABLE IF NOT EXISTS `locales` (
  `locale_id` int(11) NOT NULL AUTO_INCREMENT,
  `locale_name` varchar(255) NOT NULL,
  `locale_str` varchar(255) NOT NULL,
  PRIMARY KEY (`locale_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `locales`
--

INSERT INTO `locales` (`locale_id`, `locale_name`, `locale_str`) VALUES
(1, 'UK', 'uk'),
(2, 'USA', 'usa');

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

CREATE TABLE IF NOT EXISTS `sources` (
  `source_id` int(11) NOT NULL AUTO_INCREMENT,
  `locale_id` int(11) NOT NULL DEFAULT '1',
  `source_name` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `source_url` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL,
  PRIMARY KEY (`source_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;

--
-- Dumping data for table `sources`
--

INSERT INTO `sources` (`source_id`, `locale_id`, `source_name`, `full_name`, `source_url`, `is_active`, `sort_order`) VALUES
(2, 1, 'guardian', 'The Guardian', 'http://www.guardian.co.uk/most-viewed', 1, 1),
(3, 1, 'dailymail', 'The Daily Mail', 'http://www.dailymail.co.uk/news/mostread/index.html', 1, 3),
(4, 1, 'telegraph', 'The Telegraph', 'http://www.telegraph.co.uk', 1, 5),
(5, 1, 'thesun', 'The Sun', 'http://www.thesun.co.uk/sol/homepage/news/', 1, 4),
(6, 1, 'themirror', 'The Daily Mirror', 'http://www.mirror.co.uk/news/top-stories/', 1, 6),
(7, 1, 'independent', 'The Independent', 'http://www.independent.co.uk', 1, 7),
(8, 1, 'bbc', 'BBC', 'http://www.bbc.co.uk/news/', 1, 2),
(9, 1, 'ft', 'Financial Times', 'http://www.ft.com/home/uk', 1, 8),
(10, 1, 'yahoonews', 'Yahoo! News', 'http://uk.news.yahoo.com/most-popular/', 1, 9),
(11, 2, 'yahoonewsusa', 'Yahoo! News', 'http://news.yahoo.com/us/most-popular/', 1, 3),
(12, 2, 'cnn', 'CNN', 'http://edition.cnn.com/mostpopular/', 1, 7),
(13, 2, 'msnbc', 'MSNBC', 'http://www.msnbc.msn.com/id/7468311/', 1, 13),
(14, 2, 'nyt', 'The New York Times', 'http://www.nytimes.com/most-popular', 1, 1),
(15, 2, 'huffpo', 'The Huffington Post', 'http://www.huffingtonpost.com/news/mostpopular/', 1, 14),
(16, 2, 'fox', 'Fox News', 'http://www.foxnews.com/', 1, 11),
(17, 2, 'washpo', 'The Washington Post', 'http://www.washingtonpost.com/national', 1, 5),
(18, 2, 'latimes', 'The LA Times', 'http://www.latimes.com/', 1, 9),
(19, 2, 'abc', 'ABC', 'http://abcnews.go.com/US/MostPopular/', 1, 12),
(20, 2, 'usatoday', 'USA Today', 'http://www.usatoday.com/', 0, 6),
(21, 2, 'gawker', 'Gawker', 'http://gawker.com/', 1, 4),
(22, 2, 'wsj', 'Wall Street Journal', 'http://online.wsj.com/public/page/most_popular.html', 1, 2),
(23, 2, 'buzzfeed', 'Buzzfeed', 'http://www.buzzfeed.com/top/viral', 1, 8),
(24, 2, 'guardianusa', 'The Guardian', 'http://www.guardiannews.com/', 1, 10);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
