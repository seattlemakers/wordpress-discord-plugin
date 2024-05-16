<?php
/** @var User $user */
/** @var $membership */
/** @var string $server_id */
/** @var string $roles_channel_id */

/** @var Roles_View $roles */

/** @var bool $missing_roles */

use SeattleMakers\Discord\User;
use SeattleMakers\Roles_View;

?>

<div class="discord-steps">
    <div class="step done">
        <h3 class="title">Link</h3>
        <h4>your account</h4>

        <p>You've connected this Discord account:</p>
        <div class="actions">
            <img class="avatar" alt="user avatar"
                 src="https://cdn.discordapp.com/avatars/<?php echo $user->id ?>/<?php echo $user->avatar ?>"/>
        </div>
        <div class="actions"><strong><?php echo $user->username ?></strong></div>
    </div>
    <?php if (!isset($membership)) { ?>
        <div class="step active">
            <h3 class="title">Join</h3>
            <h4>our server</h4>
            <p>
                You haven't joined the Seattle Makers Discord server yet.
            </p>

            <div class="actions">
                <a class="grve-btn grve-btn-medium grve-extra-round grve-bg-primary-1 grve-bg-hover-black"
                   href="/discord/link">Join Discord server</a>
            </div>
        </div>
        <div class="step">
            <h3 class="title">Claim</h3>
            <h4>your roles</h4>
            <p>
                Roles in Discord grant special permissions based on your membership status.
            </p>
            <?php if (count($roles->eligible()) > 0) { ?>
                <p>You'll get the following roles:</p>

                <ul class="roles">
                    <?php foreach ($roles->eligible() as $role) { ?>
                        <li><?php echo($role->name) ?></li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <p><strong>It looks like you're not eligible for any special roles.</strong></p>
            <?php } ?>
            <p><em>If that looks incorrect, please contact us to review your profile.</em></p>
        </div>
    <?php } else { ?>
        <div class="step done">
            <h3 class="title">Join</h3>
            <h4>our server</h4>
            <p>You've joined the Seattle Makers Discord server as:</p>
            <div class="actions"><strong><?php echo $membership->nick ?></strong></div>
        </div>
        <div class="step <?php echo(count($roles->missing()) > 0 ? "active" : "done") ?>">
            <h3 class="title">Claim</h3>
            <h4>your roles</h4>
            <?php if (count($roles->missing()) > 0) { ?>
                <p>
                    Roles in Discord grant special permissions based on your membership status.
                </p>
                <p>You're still missing these roles:</p>
                <ul class="roles">
                    <?php foreach ($roles->eligible() as $role) { ?>
                        <li class="<?php echo($role->claimed ? "claimed" : "unclaimed") ?>"><?php echo($role->name) ?></li>
                    <?php } ?>
                </ul>
                <p>You'll need to claim them in the Discord App <sup><a href="/discord-roles">(?)</a></sup>:</p>
                <div class="actions">
                    <a class="grve-btn grve-btn-medium grve-extra-round grve-bg-primary-1 grve-bg-hover-black"
                       target="_blank"
                       href="https://discord.com/channels/<?php printf("%s/%s", $server_id, $roles_channel_id) ?>">Open
                        Discord</a>
                </div>
            <?php } else { ?>
                <p>You've claimed all your roles; you're all set!</p>
                <ul class="roles">
                    <?php foreach ($roles->eligible() as $role) { ?>
                        <li class="<?php echo($role->claimed ? "claimed" : "unclaimed") ?>"><?php echo($role->name) ?></li>
                    <?php } ?>
                </ul>
                <div class="actions">
                    <a class="grve-btn grve-btn-medium grve-extra-round grve-bg-primary-4 grve-bg-hover-black"
                       target="_blank"
                       href="https://discord.com/channels/<?php echo($server_id) ?>">Open Discord</a>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>