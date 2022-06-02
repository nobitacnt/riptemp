<?php
header("Content-Type: text/html; charset=UTF-8");
error_reporting(0);
include('inc/ripTemp.php');
include('inc/temp.php');
$ripTemp = new ripTemp();
$temp = new Template();

switch ($_GET['act'])
{
    case 'view':
        $result = array();
        $result = $ripTemp->get($_GET['url']);
        print_r($result['files']);
        break;
        
    case 'get':
        $result = array();
        $result = $ripTemp->get($_GET['url']);
        echo json_encode($result);
        break;
        
    case 'down':
        $msg = array();
        $msg['complete'] = $ripTemp->down($_GET['url'], $_GET['local']);
        die(json_encode($msg));
        break;
        
    default:
        $html = $temp->get('home');
        $temp->show($html);
}
?>