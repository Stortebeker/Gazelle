<?
//******************************************************************************//
//--------------- Vote on a request --------------------------------------------//
//This page is ajax!

if (!check_perms('site_vote')) {
	error(403);
}

authorize();

if (empty($_GET['id']) || !intval($_GET['id'])) {
	error(0);
}

$RequestID = $_GET['id'];

$DB->prepared_query('
	SELECT TorrentID
	FROM requests
	WHERE ID = ?', $RequestID);

if (!$DB->has_results()) {
	echo "missing";
	error(0);
}

list($FilledTorrentID) = $DB->next_record();
if ($FilledTorrentId > 0) {
	echo "filled";
	error(0);
}

$Amount = (empty($_GET['amount']) || !intval($_GET['amount']) || $_GET['amount'] < $MinimumVote)
	? $MinimumVote
	: $_GET['amount'];

$Bounty = $Amount * (1 - $RequestTax);

if ($LoggedUser['BytesUploaded'] < $Amount) {
	echo 'bankrupt';
	error(0);
}

// Create vote!
$DB->prepared_query('
	INSERT INTO requests_votes
		(RequestID, UserID, Bounty)
	VALUES
		(?, ?, ?)
	ON DUPLICATE KEY UPDATE Bounty = Bounty + ?',
	$RequestID, $LoggedUser['ID'], $Bounty, $Bounty);

$DB->prepared_query('
	UPDATE requests
	SET LastVote = NOW()
	WHERE ID = ?', $RequestID);

// Subtract amount from user
$DB->prepared_query('
	UPDATE users_main
	SET Uploaded = Uploaded - ?
	WHERE ID = ?', $Amount, $LoggedUser['ID']);
$Cache->delete_value('user_stats_'.$LoggedUser['ID']);

Requests::update_sphinx_requests($RequestID);
$DB->prepared_query('
	SELECT UserID
	FROM requests_votes
	WHERE RequestID = ?
		AND UserID != ?', $RequestID, $LoggedUser['ID']);
$UserIDs = [];
while (list($UserID) = $DB->next_record()) {
	$UserIDs[] = $UserID;
}
NotificationsManager::notify_users($UserIDs, NotificationsManager::REQUESTALERTS, Format::get_size($Amount) . " of bounty has been added to a request you've voted on!", "requests.php?action=view&id=" . $RequestID);

$Cache->delete_value("request_$RequestID");
$Cache->delete_value("request_votes_$RequestID");

$ArtistForm = Requests::get_artists($RequestID);
foreach ($ArtistForm as $Importance) {
	foreach ($Importance as $Artist) {
		$Cache->delete_value('artists_requests_'.$Artist['id']);
	}
}

echo 'success';
