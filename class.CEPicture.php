<?php
class CEpiture{
    public $api_url = '';
    const CE_URL = 'https://cefamilie.com';
    public $version = '0.1';
    private $cookie = '';
    private $page_content;
    function __construct(){
    }
    
    function CeLoadPage($page_number){
        $this->page_content = file_get_contents(self::CE_URL."/page/{$page_number}");
    }
    
    function GetMangaList(){
        if (!$this->page_content){return false;}
        preg_match('/<div class="mainleft">(?:.|\n)+?<\/ul>/',$this->page_content,$result);
        preg_match_all('/<a href="'.str_replace('/','\/',self::CE_URL).'\/(?<id>\d+)" class="zoom" rel="bookmark" title="(?<title>.+?)">/',$result[0],$result);
        $result['result'] = array();
        foreach($result['title'] as $id => $title){
            $result['result'] += array($result['id'][$id] => $title);
        }
        return $result['result'];
    }
    
    function GetMangaCover($width,$height){
        if (!$this->page_content){return false;}
        $size = "&w={$width}&h={$height}&zc=1+;a=t";
        preg_match('/<div class="mainleft">(?:.|\n)+?<\/ul>/',$this->page_content,$result);
        preg_match_all('/<img src="(.+?)&amp;w=\d+&amp;zc=\d+;a=t"/',$result[0],$result);
        foreach($result[1] as &$key){
            $key .= $size;
            unset($key);
        }
        return $result[1];
    }
    
    function GetMangaInfo($id){
        $page_content = file_get_contents(self::CE_URL."/{$id}");
        preg_match('/href="(http:\/\/exhentai.org.+?)"/',$page_content,$result);
        $ex_url = $result[1];
        $this->ExInit();
        $page_content = $this->ExLoadPage($ex_url);
        preg_match('/Showing \d+ - \d+ of (?<num>\d+) images/',$page_content,$pic_num);
        $pic_num = (int)$pic_num['num'];
        preg_match('/<div id="gdt">.+?class="c"/',$page_content,$result);
        $result = $result[0];
        preg_match_all('/<img alt=".+?" title=".+?" src="(?<pic>.+?)"/',$result,$tmp);
        $pics = $tmp['pic'];
        preg_match_all('/<a href="(?<link>.+?)">/',$result,$tmp);
        $links = $tmp['link'];
        for ($i = 1; $i < floor($pic_num / 20); $i++) {
            $page_content = $this->ExLoadPage($ex_url."?p={$i}");
            preg_match('/<div id="gdt">.+?class="c"/',$page_content,$result);
            $result = $result[0];
            preg_match_all('/<img alt=".+?" title=".+?" src="(?<pic>.+?)"/',$result,$tmp);
            $pics = array_merge($pics,$tmp['pic']);
            preg_match_all('/<a href="(?<link>.+?)">/',$result,$tmp);
            $links = array_merge($links,$tmp['link']);
        }
        preg_match('/<div class="c6" id="comment_0".+?<\/div>/',$page_content,$introduction);
        $introduction = $introduction[0];
        $introduction = str_replace('更多资源请访问：https://cefamilie.com','',preg_replace('/(<.+?>)|\s/','',$introduction));
        $mangas = $this->GetMangaPics($links);
        return array('introduction' => $introduction,'thumbnail' => $pics,'manga' => $mangas);
    }
    
    function GetMangaPics($links){
        $mangas = array();
        foreach($links as $id => $key){
            $page_content = $this->ExLoadPage($key);
            preg_match('/<img id="img" src="(?<pic>.+?)"/',$page_content,$tmp);
            $mangas += array($id => $tmp['pic']);
            sleep(1);
        }
        return $mangas;
    }
    
    function ExInit(){
        $this->cookie = json_decode(file_get_contents($this->api_url),ture);
    }
    
    function ExLoadPage($url){
        $ch = curl_init();//初始化curl
        $id = array_rand($this->cookie);
        $hash = $this->cookie[$id];
        $cookie = "ipb_member_id={$id}; ipb_pass_hash={$hash}; ";
        $cookie .= 'uconfig=tl_m-uh_y-rc_0-cats_0-xns_0-ts_l-tr_2-prn_y-dm_l-ar_0-rx_0-ry_0-ms_n-mt_n-cs_a-to_a-sa_y-oi_n-qb_n-tf_n-hp_-hk_-xl_;';
        curl_setopt($ch,CURLOPT_COOKIE,$cookie); //设置cookie
        curl_setopt($ch,CURLOPT_URL,$url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        $page_content = curl_exec($ch);//运行curl
        curl_close($ch);
        return $page_content;
    }
}
