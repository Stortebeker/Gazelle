<?php

View::show_header('Stats');

?>

<div class="thin">
    <h3 id="general">Site Statistics</h3>
    <div class="box pad" style="padding: 10px 10px 10px 20px;">
        <ul>
            <li><a href='stats.php?action=users'>User Stats</a>
            <li><a href='stats.php?action=torrents'>Torrent Stats</a>
    </div>
</div>
<?php
View::show_footer();
