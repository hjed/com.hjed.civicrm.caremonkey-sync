<h3>Connected CareMonkeyInstance</h3>


{if $connected}
    <p>You are connected!</p>
    <p><a href="{$oauth_url}">Connect</a></p>
    <!-- TODO: disconnect -->
{else}
    <p>Please authorise a CareMonkeyinstance</p>
    <p><a href="{$oauth_url}">Connect</a></p>
{/if}

{* Example: Display a translated string -- which happens to include a variable *}
<p>{ts 1=$currentTime}(In your native language) The current time is %1.{/ts}</p>
