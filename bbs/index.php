<?php
//変数初期化
$comment_array = array();
$db = array();
$dsn = null;
$escaped = array();
$error_message = array();
$pdo = null;
$postDate = null;
$success_message = null;
$stmt = null;
$res = null;
session_start();

//DB接続情報
$db['dbname'] = "kagyphp_bbsdb";  // データベース名
$db['user'] = "kagyphp_bbs";  // ユーザー名
$db['pass'] = "shitaraba146";  // ユーザー名のパスワード
$db['host'] = "mysql1.php.xdomain.ne.jp";  // DBサーバのURL
$dsn = sprintf('mysql:host=%s; dbname=%s; charset=utf8', $db['host'], $db['dbname']);

//DB接続チェック
try {
  $pdo = new PDO(
    $dsn,
    $db['user'],
    $db['pass'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
  );
} catch (PDOException $e) {
  // 接続エラーのときエラー内容を取得する
  $error_message[] = $e->getMessage();
}

//
//書き込みボタンを押した処理
if (!empty($_POST["submitButton"])) {

  //名前チェック
  if (empty($_POST["username"])) {
    $error_message[] = "名前を入力してください。";
  } else {
    $escaped['username'] = nl2br(htmlspecialchars($_POST["username"], ENT_QUOTES, "UTF-8", PDO::PARAM_STR));
  }
  //本文チェック
  if (empty($_POST["comment"])) {
    $error_message[] = "本文を入力してください。";
  } else {
    $escaped['comment'] = nl2br(htmlspecialchars($_POST["comment"], ENT_QUOTES, "UTF-8", PDO::PARAM_STR));
  }

  if (empty($error_message)) {
    //投稿日時を取得
    $postDate = date("Y-m-d H:i:s");

    //トランザクション開始
    $pdo->beginTransaction();

    try {
      //SQL文作成
      $stmt = $pdo->prepare("INSERT INTO `bbs-table`
    (`username` ,`comment` ,`postdate`) VALUES (:username, :comment,:postdate)");
      $stmt->bindParam(':username', $escaped["username"], PDO::PARAM_STR);
      $stmt->bindParam(':comment', $escaped["comment"], PDO::PARAM_STR);
      $stmt->bindParam(':postdate', $postDate, PDO::PARAM_STR);

      //クエリ実行
      $res = $stmt->execute();
      //エラーがなければコミット
      $res = $pdo->commit();
    } catch (Exception $e) {
      // エラーが発生したらロールバックする
      $pdo->rollBack();
    }

    if ($res) {
      $_SESSION['success_message'] = "書き込みに成功しました。";
    } else {
      $error_message[] = "書き込みに失敗しました";
    }
    //プリペアドステートメントを削除
    $stmt = null;

    header('Location: ./');
    exit;
  }
}

//DBからコメントデータを取得する
$sql = "SELECT `id` , `username` , `comment` , `postdate` FROM `bbs-table`;";
$comment_array = $pdo->query($sql);

//DB接続を閉じる
$pdo = null;
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <title>課題掲示板</title>
</head>

<body>
  <h1 class="title">したらば風掲示板</h1>
  <hr>
  <!-- 表示エリア -->
  <div class="board">
    <div class="mainBoard">
      <!-- 送信成功メッセージ -->
      <?php if (empty($_POST['submitButton']) && !empty($_SESSION['success_message'])) : ?>
        <p class="success_message">
          <?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>
      <!-- バリデーションチェック -->
      <?php if (!empty($error_message)) : ?>
        <?php foreach ($error_message as $value) : ?>
          <div class="error_message"><?= $value; ?></div>
        <?php endforeach; ?>
      <?php endif; ?>
      <section>
        <?php if (!empty($comment_array)) : ?>
          <?php foreach ($comment_array as $value) : ?>
            <article>
              <div class="Wrapper">
                <div class="nameArea">
                  <p class="id"><?= $value["id"]; ?></p>
                  <span>名前：</span>
                  <p class="username"><?= $value["username"]; ?></p>
                  <time>：<?= $value["postdate"]; ?></time>
                </div>
                <p class="comment"><?= $value["comment"]; ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
      <form class="formWrapper" method="POST">
        <div>
          <input type="submit" value="書き込む" name="submitButton">
          <label for="userNameLabel">名前：</label>
          <input type="text" name="username" value="名無しさん">
        </div>
        <div>
          <textarea class="commentTextArea" name="comment"></textarea>
        </div>
      </form>

    </div>
  </div>
</body>

</html>