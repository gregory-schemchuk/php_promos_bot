<?php

include 'simple_html_dom.php';

include('./infrastructure/utils/autoloader.php');
include('./app/classes/UriDB.inc');

use infrastructure\lib\DependencyContainer;


$UA = array (
    "Mozilla/5.0 (Windows; U; Windows NT 6.0; fr; rv:1.9.1b1) Gecko/20081007 Firefox/3.1b1",
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.0",
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/0.4.154.18 Safari/525.19",
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27 Safari/525.13",
    "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)",
    "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.40607)",
    "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322)",
    "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.0.3705; Media Center PC 3.1; Alexa Toolbar; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
    "Mozilla/45.0 (compatible; MSIE 6.0; Windows NT 5.1)",
    "Mozilla/4.08 (compatible; MSIE 6.0; Windows NT 5.1)",
    "Mozilla/4.01 (compatible; MSIE 6.0; Windows NT 5.1)");

$dependencyContainer = DependencyContainer::getInstance();
$telegram = $dependencyContainer->getTelegram();

$params = [];
$params["offset"] = -1;

// Init parsing
$promos = parsePromos();
storePromos($promos);


// Bot code
while (true) {
    sleep(3);
    $updates = $telegram->getUpdates($params);
    $nextUpdate = $updates[count($updates) - 1] ? $updates[count($updates) - 1]->getUpdateId() + 1 : null;
    if ($nextUpdate) {
        $params["offset"] = $nextUpdate;
    }

    foreach ($updates as $update) {
        print($update->toJson());
        if ($update && $update->getMessage() && $update->getMessage()->getText()) {
            $text = $update->getMessage()->getText();

            $firstName = "";
            try {
                $firstName = $update->getMessage()->getFrom()->getFirstName() . ": ";
            } catch (Exception $exception) {
                print $exception;
            }

            if ($text == "/get") {
                $promos = getPromos();
                $text = formStringFromPromos($promos);
            } else {
                $text = "incorrect command";
            }

            $chatId = $update->getMessage()->getChat()->getId();
            $response = $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text
            ]);
        }
    }
}

// Parsing any Promos for Aliexpress site
function parsePromos(): array {
    $promos = array();
    print("Parsing Promokod.ru\n");
    $promosPromkod = parsePromkod();
    print("Parsing Habr.com\n");
    $promosHabr = parseHabr();
    print("Parsing 7days.ru\n");
    $promos7Days = parse7Days();

    foreach ($promosPromkod as $promo) {
        array_push($promos, $promo);
    }
    foreach ($promosHabr as $promo) {
        array_push($promos, $promo);
    }
    foreach ($promos7Days as $promo) {
        array_push($promos, $promo);
    }

    return $promos;
}

function parsePromkod(): array {
    $limit = 6;
    $url = "https://www.promkod.ru/shop/aliexpress.com";
    $promos = array();
    $html = file_get_html($url);
    foreach($html->find('.offer-list-item-button-content > a') as $promoElem) {
        if ($limit <= 0) {
            break;
        }
        $limit--;

        $promoId = $promoElem->href;
        $promoId = explode("/", $promoId);
        $promoId = $promoId[count($promoId) - 1];
        $promoHtml = file_get_html('https://www.promkod.ru/shop/aliexpress.com?oid=' . $promoId);
        $promoCode = $promoHtml->find('.c-printcode', 0)->value;
        print("Parsed promo: " . $promoCode . "\n");
        array_push($promos, $promoCode);
    }
    return $promos;
}

// Blocks bot requests
function parsePromokodus(): array {
    $limit = 6;
    $url = "https://promokodus.com/campaigns/aliexpress";
    $promos = array();
    $html = file_get_html($url);
    foreach($html->find('.ch-right > a') as $promoElem) {
        if ($limit <= 0) {
            break;
        }
        $limit--;

        $promoId = $promoElem->href;
        $promoId = explode("/", $promoId);
        $promoId = $promoId[count($promoId) - 1];
        $promoHtml = file_get_html('https://promokodus.com/campaigns/aliexpress?couponId=' . $promoId);
        $promoCode = $promoHtml->find('.cm-code-wrap > input', 0)->value;
        print("Parsed promo: " . $promoCode . "\n");
        array_push($promos, $promoCode);
    }
    return $promos;
}

function parseHabr(): array {
    $limit = 6;
    $url = "https://promo.habr.com/offer/aliexpress";
    $promos = array();
    $html = file_get_html($url);
    foreach($html->find('.btn-filled-blue.coupon-btn.popup') as $promoElem) {
        if ($limit <= 0) {
            break;
        }
        $limit--;

        $promoId = $promoElem->href;
        $promoId = explode("/", $promoId);
        $promoId = $promoId[count($promoId) - 1];
        $promoHtml = file_get_html('https://promo.habr.com/offer/aliexpress?couponId=' . $promoId);
        $promoCode = $promoHtml->find('.code-wrap > input', 0)->value;
        print("Parsed promo: " . $promoCode . "\n");
        array_push($promos, $promoCode);
    }
    return $promos;
}

function parse7Days(): array {
    $limit = 6;
    $url = "https://7days.ru/promokodi/kupony-aliexpress";
    $promos = array();
    $html = file_get_html($url);
    foreach($html->find('html body div.container.sevenDays-container.sevenDays-shop-internal-content.retailer-page div.row div.col-lg-9.sevenDays-container-pl-mobile div.tab-content.sevenDays-tab-content div#all.container.sevenDays-container.tab-pane.sevenDays-tab-pane.active.sevenDays-js-coupon-list-by-retailer.js-coupon-list-by-retailer div.sevenDays-tab-content-card-shop-internal.sevenDays-tab-content-card.tab-content-card.sevenDays-js-type-coupon.js-type-coupon') as $promoElem) {
        if ($limit <= 0) {
            break;
        }
        $limit--;
        $promoCode = $promoElem->attr["data-code"];
        print("Parsed promo: " . $promoCode . "\n");
        array_push($promos, $promoCode);
    }
    return $promos;
}

function formStringFromPromos(array $promos): string {
    $str = "";
    $i = 1;
    foreach ($promos as $promo) {
        $str = $str . $i . ")" . $promo . "\n";
        $i++;
    }
    return $str;
}

function storePromos(array $promos) {
    $host = "localhost";
    $port = 5432;
    $dbname = "php_home_2";
    $user = "postgres";
    $password = "1234";
    $uridb = new UriDB($host, $port, $dbname, $user, $password);
    foreach ($promos as $promo) {
        $uridb->Add($promo);
    }
}

function getPromos(): array {
    $host = "localhost";
    $port = 5432;
    $dbname = "php_home_2";
    $user = "postgres";
    $password = "1234";
    $uridb = new UriDB($host, $port, $dbname, $user, $password);
    return $uridb->GetAllPromos();
}

function getHtml($url): string {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, getRandomUserAgent());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    $output = curl_exec($ch);
    $info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($output === false || $info != 200) {
        print("------------------------------------------------: " . $info);
        $output = null;
    }
    return $output;
}

function getRandomUserAgent() {
    srand((double)microtime()*1000000);
    global $UA;
    return $UA[rand(0,count($UA)-1)];
}
