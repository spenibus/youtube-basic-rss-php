<?php
/*******************************************************************************
youtube-basic-rss
version: 20161001-1129

spenibus.net
https://github.com/spenibus/youtube-basic-rss-php
https://gitlab.com/spenibus/youtube-basic-rss-php
*******************************************************************************/




/********************************************************************* config */
error_reporting(0);
mb_internal_encoding('UTF-8');


$CFG_HTTPS       = $_SERVER['HTTPS'];
$CFG_HOST        = $_SERVER['HTTP_HOST'];
$CFG_SELF        = $_SERVER['SCRIPT_NAME'];
$CFG_REQUEST_URI = $_SERVER['REQUEST_URI'];

$CFG_PROTOCOL_HOST = 'http'.($CFG_HTTPS ? 's' : '').'://'.$CFG_HOST;

$CFG_SELF_FULL        = $CFG_PROTOCOL_HOST.$CFG_SELF;
$CFG_REQUEST_URI_FULL = $CFG_PROTOCOL_HOST.$CFG_REQUEST_URI;


// user
$user = $_GET['user'];




/******************************************************************************/
function hsc($str) {
   return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
}




/******************************************************************** process */
if($user) {

    // user source feed url
    $srcUrl = 'https://www.youtube.com/feeds/videos.xml?channel_id='.$user;


    // get user source feed
    $feed = file_get_contents($srcUrl);


    // build DOM
    $dom = new DOMDocument();
    $dom->loadXML($feed);


    // collect namespaces
    $nodes = null;
    $xp = new DOMXPath($dom);
    $nodes = $xp->query('namespace::*');
    foreach($nodes as $node) {
        $data['xmlns'][$node->nodeName] = hsc($node->nodeName).'="'.hsc($node->nodeValue).'"';
    }

    // delete basic namespace
    unset($data['xmlns']['xmlns']);


    // init items
    $items = '';


    // collect atom entries
    $nodes = $dom->getElementsByTagName('entry');
    foreach($nodes as $node) {

        $itemTitle = $node->getElementsByTagName('title')->item(0)->nodeValue;

        $itemPubDate = gmdate(
            DATE_RSS,
            strtotime($node->getElementsByTagName('published')->item(0)->nodeValue)
        );

        $itemLink = $node->getElementsByTagName('link')->item(0)->getAttribute('href');

        $itemDescription = $node->getElementsByTagNameNS(
            'http://search.yahoo.com/mrss/',
            'description'
        )->item(0)->nodeValue;

        $itemPreviewImage = $node->getElementsByTagNameNS(
            'http://search.yahoo.com/mrss/',
            'thumbnail'
        )->item(0)->getAttribute('url');

        $items .= '
            <item>
                <title><![CDATA['.$itemTitle.']]></title>
                <pubDate>'.$itemPubDate.'</pubDate>
                <link><![CDATA['.$itemLink.']]></link>
                <description><![CDATA['.$itemDescription.'<br/><img src="'.$itemPreviewImage.'" alt="preview"/>]]></description>
            </item>';
    }


    // build xmlns list
    $xmlns = "\n   ".implode("\n   ", $data['xmlns']);


    // finalize
    $rss = '<?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0"'.$xmlns.'>
        <channel>
            <title><![CDATA['.$dom->getElementsByTagName('feed')->item(0)->getElementsByTagName('title')->item(0)->nodeValue.']]> - youtube-basic-rss</title>
            <pubDate>'.hsc(gmdate(DATE_RSS)).'</pubDate>
            <link>'.hsc($CFG_REQUEST_URI_FULL).'</link>
            <description>source: '.hsc($srcUrl).'</description>'.
            $items.'
        </channel>
    </rss>';


    // output
    header('Content-Type: application/xml; charset=utf-8');
    exit($rss);
}




/******************************************************************** default */
exit('youtube-basic-rss<br/><a href="http://spenibus.net">spenibus.net</a>');
?>