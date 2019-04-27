<?php

namespace SimpleVendor\FeedReader\Block\Widget\Post;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Psr\Log\LoggerInterface as Logger;

class PostList extends Template implements BlockInterface
{

    protected $_template = "widget/feedreader.phtml";
    protected $logger;

    public function __construct(
      \Magento\Framework\View\Element\Template\Context $context,
      Logger $logger
    ){
      parent::__construct($context);
      $this->logger = $logger;
    }

    //Get the Title from the Widget Configuration
    public function getTitle(){

      if (!$this->hasData('title')) {
        $this->setData('title','Latest Blog Posts');
      }

      return $this->getData('title');

    }

    //Get the posts to display in the widget
    public function getPosts(){
      
      //If the url is not set, return user-friendly message
      if(!$this->hasData('feed_url')){

        return 'No New Posts.';
      }

      $postData = $this->readFeed($this->getData('feed_url'));

      if($postData === false){
        return 'No New Posts.';
      }

      return $postData['channel']['item'];

    }

    private function readFeed($url){

      //Use CURL to load the RSS Feed from the provided url
      try{
        $this->logger->debug('FeedReader',['URL'=>$url]);
        $curl = curl_init();

        curl_setopt_array($curl, Array(
          CURLOPT_URL            => $url,
          CURLOPT_TIMEOUT        => 120,
          CURLOPT_CONNECTTIMEOUT => 30,
          CURLOPT_RETURNTRANSFER => TRUE,
        ));

        $data = curl_exec($curl);

        curl_close($curl);

        //If returned data is not empty, pass it off to processing function
        if(empty($data)){

          //No data was returned from the CURL operation
          return false;
         
        }else{
          //$this->logger->debug('Returned Data',[$this->xmltoarray($data)]);
          $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
          $this->logger->debug('Blog Data',[$this->xmltoarray($xml)]);

        }

        return $this->xmltoarray($xml);

      }catch (\Exception $e){

        //There was an error, return false
        $this->logger->debug('Error Getting Blog Data',[$e]);
        return false;

      }
      
    }

    private function xmltoarray($xml){

        if (is_object($xml)){
             $xml = get_object_vars($xml);
        }
  
        return (is_array($xml)) ? array_map(array($this,'xmltoarray'),$xml) : $xml;    
    
    }

}

?>