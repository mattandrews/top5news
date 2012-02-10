<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Scraper extends CI_Controller {

	function index() {
		echo "nothing to see here";
	}

	// we can now call it in stages
	function scrape($limit, $offset) {
		set_time_limit(15 * 60);

		// delete yesterday's news
		$this->db->query("DELETE FROM news WHERE created < DATE_SUB(NOW(), INTERVAL 1 DAY)");

		$this->load->library('simple_html_dom');
		
		$this->db->offset($offset);
		$this->db->limit($limit);
		$sites = $this->db->get('sources')->result_array();

		foreach($sites as $site) {
			$html = file_get_html($site['source_url']);
			$stories = $this->_filter_out_headlines($site['source_name'], $html);
			$this->_add_stories_to_db($stories, $site['source_id']);
		}

		// bit hacky, what if we add more rows? could do with being dynamic at some point
		if($offset == 4) {
			$this->find_eligible_tweets();
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
			$this->db->insert('news_archive', $news);
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

			$list = $html->find('div.site-most-read div', 0);
			$list = $list->find('div', 1);
			foreach($list->find('div.article') as $key=>$item) {
				$thing = $item->find('h2');
				$thing = $thing[0];
				$prefix = 'http://www.dailymail.co.uk';
				$link = $prefix . $thing->find('a', 0)->href;
				$headline = trim(strip_tags($thing->find('a', 0)->innertext));
				$image = NULL;
				$image = $item->find('img', 0);
				if($image) {
					$image = $image->src;
				}
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
			if($div) {
				$list = $div->find('li', 0);
				foreach($list as $item) {
					$link = $item->find('h4 a', 0)->href;
					$headline = $item->find('h4 a', 0)->innertext;
					$image = $item->find('img', 0);
					if($image) {
						$image = $image->src;
					}
					$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
				}
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
		} elseif($site === 'ft') {
			$headings = $html->find('h3.railComponentHeading');
			foreach($headings as $heading) {
				$heading_text = trim($heading->innertext);
				if($heading_text === "Most popular") {
					$list = $heading->next_sibling();
					foreach($list->find('li') as $item) {
						$link = $item->find('a', 0)->href;
						$headline = trim($item->find('a', 0)->innertext);
						$image = NULL;
						$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
					}
				}
			}
		}
		
		$stories = array_slice($stories, 0, 5); // only keep 5 items
		return $stories;
	}

	/*

	function update_top_positions() {
		$this->db->group_by('source_id');
		$this->db->order_by('created DESC');
		$this->db->select('url, source_id, created');
		$top_stories = $this->db->get_where('news', array('rank' => 1))->result_array();
		foreach($top_stories as $story) {
			$s = array('source_id' => $story['source_id'], 'story_id' => $story['url'], 'date_created' => $story['created']);
			$this->db->insert('top_story_history', $s);
		}

		$this->find_eligible_tweets();

	}
	*/

	function find_eligible_tweets() {
		
		// first get the current top stories
		$this->db->order_by('source_id, created DESC');
		$this->db->group_by('source_id');
		$current_top_stories = $this->db->get_where('news', array('rank' => 1))->result_array();

		$stories_to_tweet = array();

		// now pull out the newest top story and save the rest in an array
		foreach($current_top_stories as $s) {
			$num_times_top = $this->db->get_where('news_archive', array('url' => $s['url'], 'rank' => 1))->result_array();
			if(count($num_times_top) === 1) {
				$stories_to_tweet[] = $num_times_top[0];
			}
		}

		if(!empty($stories_to_tweet)) {

			// now tweet them
			foreach($stories_to_tweet as $t) {
				$link_length = 20; // default for t.co link and spaces etc
				$headline = $t['headline'] . ': ';
				$link = $t['url'];
				$hashtag = ' #top5news';
				$limit = 140;
				$headline_and_link = $headline . $link;
				$headline_and_link_length = count($headline) + $link_length;
				if ($headline_and_link_length < ($limit - count($hashtag)) ) {
					$status = $headline_and_link . $hashtag;
				} else {
					$status = $headline_and_link;
				}

				$this->_post_tweet($status);
			}
		}
	}


	function _post_tweet($tweet) {
		require_once('./inc/twitteroauth.php');
		$connection = new TwitterOAuth('pGZMx40oH6YPZTNkmmADXQ', 'PiaDBDMRnJPCOm66vbhwzZG8TC8VJLGr1x3ptCm6pY', '434395002-tDwIprDLEWyNKbr1vXa8LiLFvloA6P91E28jUgzH', 'PQypy9Z2r67TdCJ9RMnGMhzTQIVuPVUCqq72731bk6k');
		$connection->post('statuses/update', array('status' => $tweet));
	}
}