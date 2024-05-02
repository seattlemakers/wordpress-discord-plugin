<?php
/** @var \SeattleMakers\Discord\User $user */
/** @var $membership */
/** @var string $server_id */
/** @var array $roles */
?>
<p>You're connected as <?php echo $user->username ?>!</p>
<img class="avatar" alt="user avatar"
     src="https://cdn.discordapp.com/avatars/<?php echo $user->id ?>/<?php echo $user->avatar ?>"/>

<?php if (!isset($membership)) { ?>
    It looks like you haven't joined our server yet; click here to join:
    <a href="/discord/link">
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="228.75" height="28"
             role="img" aria-label="Join the Seattle Makers discord server"><title>Join the Seattle Makers discord server!</title>
            <g shape-rendering="crispEdges">
                <rect width="82.25" height="28" fill="#5865f2"/>
                <rect x="82.25" width="146.5" height="28" fill="#555"/>
            </g>
            <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif"
               text-rendering="geometricPrecision" font-size="100">
                <image x="9" y="7" width="14" height="14"
                       xlink:href="data:image/svg+xml;base64,PHN2ZyBmaWxsPSJ3aGl0ZSIgcm9sZT0iaW1nIiB2aWV3Qm94PSIwIDAgMjQgMjQiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHRpdGxlPkRpc2NvcmQ8L3RpdGxlPjxwYXRoIGQ9Ik0yMC4zMTcgNC4zNjk4YTE5Ljc5MTMgMTkuNzkxMyAwIDAwLTQuODg1MS0xLjUxNTIuMDc0MS4wNzQxIDAgMDAtLjA3ODUuMDM3MWMtLjIxMS4zNzUzLS40NDQ3Ljg2NDgtLjYwODMgMS4yNDk1LTEuODQ0Ny0uMjc2Mi0zLjY4LS4yNzYyLTUuNDg2OCAwLS4xNjM2LS4zOTMzLS40MDU4LS44NzQyLS42MTc3LTEuMjQ5NWEuMDc3LjA3NyAwIDAwLS4wNzg1LS4wMzcgMTkuNzM2MyAxOS43MzYzIDAgMDAtNC44ODUyIDEuNTE1LjA2OTkuMDY5OSAwIDAwLS4wMzIxLjAyNzdDLjUzMzQgOS4wNDU4LS4zMTkgMTMuNTc5OS4wOTkyIDE4LjA1NzhhLjA4MjQuMDgyNCAwIDAwLjAzMTIuMDU2MWMyLjA1MjggMS41MDc2IDQuMDQxMyAyLjQyMjggNS45OTI5IDMuMDI5NGEuMDc3Ny4wNzc3IDAgMDAuMDg0Mi0uMDI3NmMuNDYxNi0uNjMwNC44NzMxLTEuMjk1MiAxLjIyNi0xLjk5NDJhLjA3Ni4wNzYgMCAwMC0uMDQxNi0uMTA1N2MtLjY1MjgtLjI0NzYtMS4yNzQzLS41NDk1LTEuODcyMi0uODkyM2EuMDc3LjA3NyAwIDAxLS4wMDc2LS4xMjc3Yy4xMjU4LS4wOTQzLjI1MTctLjE5MjMuMzcxOC0uMjkxNGEuMDc0My4wNzQzIDAgMDEuMDc3Ni0uMDEwNWMzLjkyNzggMS43OTMzIDguMTggMS43OTMzIDEyLjA2MTQgMGEuMDczOS4wNzM5IDAgMDEuMDc4NS4wMDk1Yy4xMjAyLjA5OS4yNDYuMTk4MS4zNzI4LjI5MjRhLjA3Ny4wNzcgMCAwMS0uMDA2Ni4xMjc2IDEyLjI5ODYgMTIuMjk4NiAwIDAxLTEuODczLjg5MTQuMDc2Ni4wNzY2IDAgMDAtLjA0MDcuMTA2N2MuMzYwNC42OTguNzcxOSAxLjM2MjggMS4yMjUgMS45OTMyYS4wNzYuMDc2IDAgMDAuMDg0Mi4wMjg2YzEuOTYxLS42MDY3IDMuOTQ5NS0xLjUyMTkgNi4wMDIzLTMuMDI5NGEuMDc3LjA3NyAwIDAwLjAzMTMtLjA1NTJjLjUwMDQtNS4xNzctLjgzODItOS42NzM5LTMuNTQ4NS0xMy42NjA0YS4wNjEuMDYxIDAgMDAtLjAzMTItLjAyODZ6TTguMDIgMTUuMzMxMmMtMS4xODI1IDAtMi4xNTY5LTEuMDg1Ny0yLjE1NjktMi40MTkgMC0xLjMzMzIuOTU1NS0yLjQxODkgMi4xNTctMi40MTg5IDEuMjEwOCAwIDIuMTc1NyAxLjA5NTIgMi4xNTY4IDIuNDE5IDAgMS4zMzMyLS45NTU1IDIuNDE4OS0yLjE1NjkgMi40MTg5em03Ljk3NDggMGMtMS4xODI1IDAtMi4xNTY5LTEuMDg1Ny0yLjE1NjktMi40MTkgMC0xLjMzMzIuOTU1NC0yLjQxODkgMi4xNTY5LTIuNDE4OSAxLjIxMDggMCAyLjE3NTcgMS4wOTUyIDIuMTU2OCAyLjQxOSAwIDEuMzMzMi0uOTQ2IDIuNDE4OS0yLjE1NjggMi40MTg5WiIvPjwvc3ZnPg=="/>
                <text transform="scale(.1)" x="496.25" y="175" textLength="412.5" fill="#fff" font-weight="bold">Seattle Makers
                </text>
                <text transform="scale(.1)" x="1555" y="175" textLength="1225" fill="#fff" font-weight="bold">Join us on Discord</text>
            </g>
        </svg></a>
<?php } else { ?>
    <p>You're already in the Seattle Makers discord server; <a href="https://discord.com/channels/<?php echo $server_id ?>">go to Discord</a> to start chatting!</p>
<?php } ?>

<?php if (count($roles) > 0) {?>
    <p>You should have the following roles in discord:</p>
    <ul>
    <?php foreach ($roles as $role) { ?>
        <li><?php echo($role) ?></li>
    <?php } ?>
    </ul>
    <p>To claim the roles in the Discord App, click on the Seattle Makers server name (in the top left), and choose "Linked Roles" from the menu.</p>
<?php } ?>

