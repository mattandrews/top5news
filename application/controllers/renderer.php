<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Renderer extends CI_Controller {
    
    function index() {
        $this->load->library('prettydate');
        $sql = "SELECT news1.*, sources.*
                FROM news news1
                LEFT OUTER JOIN news news2
                  ON (news1.source_id = news2.source_id AND news1.id < news2.id)
                JOIN sources ON news1.source_id = sources.source_id
                GROUP BY news1.id
                HAVING COUNT(*) < 5
                ORDER BY news1.source_id, rank ASC";
        $news = $this->db->query($sql)->result_array();
        $data['news'] = array();
        
        foreach($news as $n) {
            $data['news'][$n['full_name']][] = $n;
        }
        $this->load->view('index', $data);
    }
}