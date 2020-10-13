Simple StudOn Service for fau.tv
--------------------------------

Call example:

curl -H 'Accept: application/json' -H "Authorization: Bearer {token}" \
    https://www.studon.fau.de/studon/Services/WebServices/VP/server.php/check/{user}/{type}/{id}?client_id=StudOn

Short link:
curl -H 'Accept: application/json' -H "Authorization: Bearer {token}" https://www.studon.fau.de/vp/check/{user}/{type}/{id}

{token} is the authorization token of fau.tv for studOn
{user}  is the IDM user name whose access has to be checked
{type}  is either "course" or "clip"
{id}    is the id of the course or clip

