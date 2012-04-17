<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Scraper extends CI_Controller {

	function index() {
		echo "nothing to see here";
	}

	// we can now call it in stages
	function scrape($locale, $limit, $offset) {
		set_time_limit(15 * 60);

		// delete yesterday's news
		$this->db->query("DELETE FROM news WHERE created < DATE_SUB(NOW(), INTERVAL 1 DAY)");

		$this->load->library('simple_html_dom');
		
		$this->db->offset($offset);
		$this->db->limit($limit);
		$this->db->where(array('locale_id' => $locale, 'is_active' => 1));
		$sites = $this->db->get('sources')->result_array();

		// for debug
		//echo '<pre>'; print_r($sites);

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
		
		if($site === 'guardian') { // duped at end for US edition
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
			$div = $html->find('div.mostRead div ol', 0);
			$list = $div->find('li');
			foreach($list as $item) {
				$link = $item->find('a', 0)->href;
				$headline = $item->find('a', 0)->innertext;
				$image = NULL;
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
		} elseif($site === 'yahoonews') {
			$div = $html->find('ul.most-popular-ul', 0);
			$list = $div->find('li');
			$prefix = 'http://uk.news.yahoo.com';
			foreach($list as $item) {
				$link = $prefix . $item->find('h4 a', 0)->href;
				$headline = $item->find('h4 a', 0)->innertext;
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		}


		// BEGIN USA CHECKS

		elseif($site === 'yahoonewsusa') {
			$div = $html->find('ul.most-popular-ul', 0);
			$list = $div->find('li');
			$prefix = 'http://news.yahoo.com';
			foreach($list as $item) {
				$link = $prefix . $item->find('h4 a', 0)->href;
				$headline = $item->find('h4 a', 0)->innertext;
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'cnn') {
			$prefix = "http://edition.cnn.com";
			$list = $html->find('div#cnnMostPopularItem');
			foreach($list as $item) {
				$link = $item->find('div.cnnMPContentHeadline a', 0)->href;
				if (strpos($link, $prefix) === FALSE) {
					$link = $prefix . $link;
				}
				$headline = $item->find('div.cnnMPContentHeadline a', 0)->innertext;
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'msnbc') {
			$div = $html->find('div#grid_21604548 div#cell2', 0);
			$list = $div->find('div.story');
			foreach($list as $item) {
				$firstlink = $item->find('h6 a', 0);
				
				// stupid icon image
				if ($firstlink->class == 'icoNew') {
					$link = $item->find('h6 a', 1)->href;
					$headline = $item->find('h6 a', 1)->innertext;
				} else {
					$link = $item->find('h6 a', 0)->href;
					$headline = $item->find('h6 a', 0)->innertext;
				}
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'nyt') {
			$div = $html->find('ol.mostPopularList', 1);
			$list = $div->find('li');
			foreach($list as $item) {
				$link = $item->find('a', 0)->href;
				$headline = trim($item->find('a', 0)->innertext);
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'huffpo') {
			$div = $html->find('div.snp_most_popular', 0); // only returns 4, bah
			$list = $div->find('div.snp_most_popular_entry');
			foreach($list as $item) {
				// offset for img
				$link = $item->find('a.track_page_article', 1)->href;
				$headline = trim($item->find('a.track_page_article', 1)->innertext);
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'fox') {
			$div = $html->find('div.listings', 1); // only returns 3, bah
			$list = $div->find('ul.list li');
			foreach($list as $item) {
				$link = $item->find('a', 0)->href;
				$headline = trim($item->find('a', 0)->innertext);
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'washpo') {
			$div = $html->find('div.most-post ul li ol', 0);
			$list = $div->find('li');
			foreach($list as $item) {
				$link = $item->find('a', 0)->href;
				$headline = trim($item->find('a', 0)->innertext);
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'latimes') {
			$div = $html->find('ul.feedMasherList', 1);
			$list = $div->find('li');
			foreach($list as $item) {
				$link = $item->find('a', 0)->href;
				$headline = trim($item->find('a', 0)->innertext);
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'abc') {
			$div = $html->find('div#listpack', 0);
			$list = $div->find('div.pane ul li');
			foreach($list as $item) {
				$class = $item->class;
				$offset = 1;
				if($class == 'leaditem') { $offset = 0; }
				$link = $item->find('a', $offset)->href;
				$headline = trim($item->find('a', $offset)->innertext);
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'usatoday') {
			$div = $html->find('div.ranked-list ol', 0);
			$list = $div->find('li');
			foreach($list as $item) {
				$link = $item->find('a', 0)->href;
				$headline = trim($item->find('a', 0)->innertext);
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'wsj') {
			$prefix = "http://online.wsj.com";
			$div = $html->find('div#mvtab0 table', 0);
			$list = $div->find('tr');
			foreach($list as $item) {
				$link = $prefix . $item->find('td a', 0)->href;
				$headline = trim($item->find('td a', 0)->innertext);
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'buzzfeed') {
			$prefix = "http://www.buzzfeed.com";
			$div = $html->find('ul.result_list', 0);
			$list = $div->find('li');
			foreach($list as $item) {
				$link = $prefix . $item->find('div.info h3 a', 0)->href;
				$headline = trim($item->find('div.info h3 a', 0)->innertext);
				$image = NULL;
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		} elseif($site === 'guardianusa') {
			$list = $html->find('div.#att-most-viewed ol li');
			foreach($list as $item) {
				$link = $item->find('a', 0)->href;
				$headline = $item->find('a', 0)->innertext;
				$image = $item->find('span.image-optional a img', 0);
				if($image) {
					$image = $image->src;
				}
				$stories[] = array('link' => $link, 'headline' => $headline, 'image' => $image);
			}
		}
		
		// see if any didn't scrape
		echo $this->_report_error($stories, $site);

		// for debug
		//echo '<pre>'; print_r($stories); die;

		$stories = array_slice($stories, 0, 5); // only keep 5 items
		return $stories;
	}

	function _report_error($stories, $name) {
		if (empty($stories)) {
			return "didn't find anything for " . $name . "<br />";
		}
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
				$headline = strip_tags($t['headline']) . ': ';
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