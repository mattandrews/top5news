<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Renderer extends CI_Controller {
    
    function index() {
        $this->output->cache(15); // 15 min cache. yay
        //$this->output->enable_profiler();

        $this->load->library('prettydate');
        $this->load->helper(array('mobile', 'text'));
        $data['is_mobile'] = mobile_device_detect(true,false,true,true,true,true,true,false,false);

        $sql = "SELECT news1.*, sources.*
                FROM news news1
                LEFT OUTER JOIN news news2
                  ON (news1.source_id = news2.source_id AND news1.id < news2.id)
                JOIN sources ON news1.source_id = sources.source_id
                GROUP BY news1.id
                HAVING COUNT(*) < 5
                ORDER BY sources.sort_order, news1.source_id, rank ASC";
        
        $news = $this->db->query($sql)->result_array();
        
        $data['news'] = array();
        foreach($news as $n) {
            $data['news'][$n['full_name']][] = $n;
        }

        $meta = $this->meta();
        foreach($meta as $m) {
            $m['source_name'] = 'meta';
            $data['news']['meta'][] = $m;
        }

        $this->load->view('index', $data);
    }

    function email() {
        $sender_name = $this->input->post('name');
        $sender_email = $this->input->post('email');
        $comment = $this->input->post('comments');

        $this->load->library('email');
        //$config['protocol'] = 'mail';
        //$this->email->initialize($config);

        $address = 'top5newsuk@gmail.com';
        $this->email->to($address);
        $this->email->from($sender_email);
        $this->email->subject('top5news.co.uk feedback from: ' . $sender_name);
        $this->email->message('Feedback on top5news.co.uk received from ' . $sender_name . ' (email: ' . $sender_email .'): ' . $comment);
        $this->email->send();
    }

    function track() {
        $id = $this->input->post('id');
        $this->db->insert('clicktracker', array('news_id' => $id));
    }

    function meta() {
        $sql = 'SELECT COUNT(clicktracker.news_id) AS num_clicks, news.*, sources.* FROM clicktracker JOIN news ON clicktracker.news_id = news.id JOIN sources ON news.source_id = sources.source_id GROUP BY clicktracker.news_id ORDER BY num_clicks DESC, date_clicked DESC LIMIT 5';
        $top_5_top_5 = $this->db->query($sql)->result_array();
        return $top_5_top_5;
    }
}