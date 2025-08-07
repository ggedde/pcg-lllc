<?php
/**
 * Login View
 */

use MapWidget\User;

?>
<body>
    <div class="mw-400 m-auto mt-3">
        <h4>Sign In</h4>
        <div class="p-3 border rounded mt-1">
            <?php if (User::$loginError) { ?>
                <div class="border border-error rounded p-2 color-error mb-2">
                    <?= User::$loginError; ?>
                </div>
            <?php } ?>
            <form method="post" class="row columns-1 g-2">
                <label>
                    <h5>Username</h5>
                    <input name="username" />
                </label>
                <label>
                    <h5>Password</h5>
                    <input type="password" name="password" />
                </label>
                <button class="btn mt-2" type="submit">Sign In</button>
            </form>
        </div>
    </div>
</body>
