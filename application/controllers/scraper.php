<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Scraper extends CI_Controller {

	function index() {
		echo "nothing to see here";
	}

	// we can now call it in stages
	function scrape($offset, $limit) {
		set_time_limit(180);
		$this->load->library('simple_html_dom');
		
		$this->db->offset($limit);
		$this->db->limit($offset);
		$sites = $this->db->get('sources')->result_array();

		foreach($sites as $site) {
			$html = file_get_html($site['source_url']);
			$stories = $this->_filter_out_headlines($site['source_name'], $html);
			$this->_add_stories_to_db($stories, $site['source_id']);
		}
		
		echo "all done!";
	}
	
	function _add_stories_to_db($stories, $site_id) {
		$rank = 1;
		foreach($stories as $story) {
			$news = array(
				'source_id' => $site_id,
				'url' => $story['link'],
				'rank' => $rank,
				'headline' => $story['headline'],
				'thumbnail' => $story['image']
			);
			$this->db->insert('news', $news);
			$rank++;
		}
	}
	
	function _filter_out_headlines($site, $html) {
		$stories = array();
		
		if($site === 'guardian') {
			$list = $html->find('div.most-viewed-section ol li');
			foreach($list as $item) {
				$link = $item->find('a', 0)->href;
				$headline = $item->find('a', 0)->innertext;
				$image = $item->find('span.image-optional a img', 0);
				if($image) {
					$image = $image->src;
				}
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'dailymail') {
			$list = $html->find('div[id=r0c1p35-2] ul li');
			foreach($list as $item) {
				$prefix = 'http://www.dailymail.co.uk';
				$link = $prefix . $item->find('a', 0)->href;
				$headline = trim(strip_tags($item->find('a', 0)->innertext));
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'telegraph') {
			$list = $html->find('div[id=div-TODAY] ol li');
			foreach($list as $item) {
				$prefix = 'http://www.telegraph.co.uk';
				$link = $prefix . $item->find('a', 0)->href;
				$headline = $item->find('a', 0)->innertext;
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'thesun') {
			$list = $html->find('div.most-read ul li');
			foreach($list as $item) {
				$prefix = 'http://www.thesun.co.uk';
				$link = $prefix . $item->find('a', 0)->href;
				$headline = $item->find('a', 0)->innertext;
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'themirror') {
			$div = $html->find('div.storylst-body div ul', 0);
			$list = $div->find('li');
			foreach($list as $item) {
				$link = $item->find('h4 a', 0)->href;
				$headline = $item->find('h4 a', 0)->innertext;
				$image = $item->find('img', 0);
				if($image) {
					$image = $image->src;
				}
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'independent') {
			$list = $html->find('div.mostViewed ul li');
			foreach($list as $item) {
				$link = $item->find('a', 0)->href;
				$headline = $item->find('a', 0)->innertext;
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'bbc') {
			$div = $html->find('div[id=most-popular] div.panel', 1); // finds second tab
			$list = $div->find('ol li');
			foreach($list as $item) {
				$link = $item->find('a', 0)->href;
				$span = $item->find('a span', 0)->outertext;
				$headline = $item->find('a', 0)->innertext;
				$headline = trim(str_replace($span, '', $headline));
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'sky') {
			$div = $html->find('div[id=navTeaser] div.promoSlotRight div.module_body', 0);
			$list = $div->find('ul li');
			foreach($list as $item) {
				$prefix = 'http://news.sky.com';
				$link = $prefix . $item->find('a', 0)->href;
				$headline = trim($item->find('a', 0)->innertext);
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		}
		
		$stories = array_slice($stories, 0, 5); // only keep 5 items
		return $stories;
	}
}