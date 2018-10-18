<?php

//Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';

// アクセストークンを使いCurlHTTPClientをインスタンス化
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));

// CurlHTTPClientとシークレットを使いLINEBotをインスタンス化
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);

// LINE Messaging APIがリクエストに付与した署名を取得
$signature = $_SERVER['HTTP_' . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

// 署名が正当かチェック。政党であればリクエストをパースし配列へ
// 不正であれば例外の内容を出力
try{
  $events = $bot->parseEventRequest(file_get_contents('php://input'),$signature);

} catch(\LINE\LINEBot\Exception\InvalidSignatureException $e) {
  error_log('parseEventRequest failed. InvalidSignatureException =>'.var_export($e, true));

} catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
  error_log('parseEventRequest failed. UnknownEventTypeException =>'.var_export($e, true));

} catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
  error_log('parseEventRequest failed. UnknownMessageTypeException =>'.var_export($e, true));

} catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
  error_log('parseEventRequest failed. InvalidEventRequestException =>'.var_export($e, true));
}

//配列に格納された各イベントをループで処理
foreach ((array)$events as $event){
  // MessageEventクラスのインスタンスでなければ処理をスキップ
  if(!($event instanceof \LINE\LINEBot\Event\MessageEvent)){
    error_log('Non Message event has come');
    continue;
  }
  // TextMessageBuilderクラスのインスタンスでなければ処理をスキップ
  if(!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)){
    error_log('Non Message event has come');
    continue;
  }
  //オウム返し
  $bot->replyText($event->getReplyToken(), $event->getText());
}

//テキストを返信。引数はLINEBot、返信先、テキスト
function replyTextMessage($bot,$replyToken,$text) {
  // 返信を行いメッセージを取得
  // TextMessageBuilderの引数はテキスト
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));

  //レスポンスが異常な場合
  if(!$response->isSucceeded()){
    //エラー内容を出力
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//画像を返信。引数はLINEBot、返信先、画像URL、サムネイルURL
function replyImageMessage($bot,$replyToken,$originalImageUrl,$previewImageUrl){
  // ImageMessageBuilderの引数は画像URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($originalImageUrl, $previewImageUrl));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//位置情報を返信。引数はLINEBot、返信先、タイトル、住所、緯度、経度
function replyLocationMessage($bot, $replyToken, $title, $address, $lat, $lon) {
  //LocationMessageBuilderの引数はダイアログのタイトル、住所、緯度、経度
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder($title,$address,$lat,$lon));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//スタンプを返信。引数はLINEBot、返信先、スタンプのパッケージID、スタンプID
function replyStickerMessage($bot, $replyToken, $packageId, $stickerId) {
  //StickerMessageBuilderの引数はスタンプのパッケージID、スタンプID
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder($packageId, $stickerId));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//動画を返信。引数はLINEBot、返信先、動画URL、サムネイルURL
function replyVideoMessage($bot, $replyToken, $originalContentUrl, $previewImageUrl) {
  //VideoMessageBuilderの引数は動画URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\VideoMessageBuilder($originalContentUrl, $previewImageUrl));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//オーディオファイルを返信。引数はLINEBot、返信先、ファイルのURL、ファイルの再生時間
function replyAudioMessage($bot, $replyToken, $originalContentUrl, $audioLength) {
  //AudioMessageBuilderの引数は動画URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\AudioMessageBuilder($originalContentUrl, $audioLength));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//複数のメッセージをまとめて返信。引数はLINEBot、返信先、メッセージ(可変長引数)
function replyMultiMessage($bot, $replyToken, ...$msgs) {
  //MultiMessageBuilderをインスタンス化
  $builder = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
  // ビルダーにメッセージをすべて追加
  foreach($msgs as $value){
    $builder->add($value);
  }
  $response = $bot->replyMessage($replyToken,$builder);
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

// Buttonsテンプレートを送信。引数はLINEBot、返信先、代替テキスト、画像URL、タイトル、本文、アクション(可変長引数)
function replyButtonsTemplate($bot, $replyToken, $alternativeText,$imageUrl,$title,$text, ...$actions) {
  // アクションを格納する配列
  $actionArray = array();
  // アクションをすべて追加
  foreach($actions as $value) {
    array_push($actionArray, $value);
  }
  //TemplateMessageBuilderの引数は代替テキスト、ButtonTemplateBuilder
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    // ButtonTemplateBuilderの引数はタイトル、本文
    // 画像URL、アクションの配列
    new \LINE\LINEBot\MessageBuilder\ButtonTemplateBuilder($title,$text,$imageUrl,$actionArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//Confirmテンプレート返信。引数はLINEBot、返信先、代替テキスト、本文、アクション(可変長引数)
function replyConfirmTemplate($bot, $replyToken, $alternativeText,$text, ...$actions) {
  $actionArray = array();
  foreach($actions as $value) {
    array_push($actionArray, $value);
  }
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    // Confirmテンプレートの引数はテキスト、アクションの配列
    new \LINE\LINEBot\MessageBuilder\ConfirmTemplateBuilder($text,$actionArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//Carouselテンプレートを返信。引数はLINEBot、返信先、メッセージ(可変長引数)
//ダイアログの配列
function replyCarouselTemplate($bot, $replyToken, $alternativeText, $columnArray) {
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    // Carouselテンプレートの引数はダイアログの配列
    new \LINE\LINEBot\MessageBuilder\CarouselTemplateBuilder($columnArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

// 入力されたテキストを取得
$location = $event->getText();
//住所ID変化用
$locationId;
//XMLファイルをパースするクラス
$client = new Goutte\Client();
//XMLファイルを取得
$crawler = $client->request('GET', 'http://wheather.livedoor.com/forecast/rss/primary_area.xml');
//市名のみを抽出しユーザが入力した市名と比較
foreach ($crawler->filter('channel ldWheather|source pref city') as $city) {
  // 一致すれば住所IDを取得し処理抜ける
  if($city->getAttribute('title') == $location || $city->getAttribute('title') . "市" == $location){
    $locationId = $city->getAttribute('id');
    break;
  }
  // 一致するものがなければ
  if(empty($locationId)) {
    // 候補の配列
    $suggestArray = array();
    // 件名を抽出しユーザーが入力した件名と比較
    foreach ($crawler=>filter('channel ldWheather|source pref') as $pref) {
      // 一致すれば
      if(strpos($perf->getAttribute('title'),$location) !== false){
        // その件に属する市を配列に追加
        foreach ($pref->childNodes as $child) {
          if($child instanceof DOMElement && $child->nodeName == 'city'){
            array_push($suggestArray,$child->getAttribute('title'));
          }
        }
        break;
      }
    }
    // 候補が存在する場合
    if(count($suggestArray) > 0){
      //アクションの配列
      $actionArray = array();
      // 候補をすべてアクションにして追加
      foreach ($suggestArray as $city) {
        array_push($actionArray, new LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder ($city, $city));
      }
      // Buttonsテンプレートを返信
      $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder('見つかりませんでした。',new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder('もしかして？', null, $actionArray));
      $bot->replyMessage($event->getReplyToken(),$builder);
      // 候補が存在しない場合
    } else{
      // 正しい入力方法を返信
      replyTextMessage($bot, $event->getReplyToken(),'入力された地名が見つかりませんでした。市を入力してください。');
    }
    // 以降の処理はスキップ
    continue;
  }
  replyTextMessage($bot, $event->getReplyToken(),$location . 'の住所IDは' . $locationId . "です。")
}

?>
