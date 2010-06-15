# ajax-proxy

## Overview

This package is a PHP class and script to proxy AJAX request accross domains.
It is intended to work around the cross-domain request restrictions found in
most browsers.

This proxy does not simply forward request and response bodies, it also forwards
cookies, user-agent strings, and content-types. This makes is extensively useful
for performing operations like ajax-based logins accross domains (which usually
rely on cookies).

The class' functionality is encapsulated in a single, standalone class, so
incorporation into larger applications or frameworks can be easily done. It may
also be used alone.

## Scenario

Suppose we were writing the client-side portion of fun.example.com. If the site
relies on ajax-based logins, and the login controller/authority is at
login.example.com, then we're going to have trouble making logins work accross
domains.

This is where the proxy comes in. We want to have ajax requests sent to
fun.example.com, and then routed, or 'proxied' to login.example.com

## Setup

In this package, /src/proxy.php is the standalone class and script. We would
place this script at a location on fun.example.com, perhaps at
fun.example.com/proxy.php

At the bottom of proxy.php, there are two lines of code:

    $proxy = new Proxy('http://login.example.com/');
    $proxy->execute();

The first line created the proxy object. The proxy object performs the entirety
of the work. The Proxy class' constructor takes 2 arguments, the last of which
is optional.

1. `$forward_host`, which is where all requests to the proxy will be routed.
2. `$allowed_hostname`, which an optional paramater. Is this is supplied, it
   should be a hostname or ip address that you would like to restrict requests
   to. This way, you can make sure that only requests from fun.example.com ever
   access the proxy.

The second line executes the proxy request. In the event of failure, the proxy
will halt and produce an error message. Error messages in this application are
generally very specific.

Finally, in the javascript, suppose you initially wanted to make requests to
login.example.com/user/auth. From the ajax, you would now call
fun.example.com/proxy.php?route=user/auth . The route parameter will be
concatenated with the `$forward_host` argument in the proxy's contructor to
produce a final url. The request will be made and the response will be sent back
to the client.

Note that the lines at the bottom of proxy.php may be removed if you want to
incorporate the class into a larger application or framework.

## Gotchas

If you are using this proxy and you expect to use cookies, make sure that your
web application is not validating cookies by IP address. This is a common
setting in web frameworks such as CodeIgniter and Kohana which can be easily
disabled. Validations such as cookie-expiration and user-agent are acceptable.

Another gotcha with cookies: Make sure that cookies being emitted from the
target server are going to be accepted by the client. That is, if
login.example.com will be sending authentication or session cookies, make sure
they have an appropriate cookies domain set so that the client's browser will
accept them at fun.example.com (for example, a domain of '*.example.com' would
work fine.