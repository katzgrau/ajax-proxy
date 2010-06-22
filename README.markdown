# ajax-proxy

An extensively easy-to-use proxy class and script for facilitating cross-domain
ajax calls that **supports cookies** and has minimal dependencies
(**works without cURL!**)

Written and maintained by [Kenny Katzgrau](http://codefury.net) @ [HUGE](http://hugeinc.com)

## Overview

This package is a PHP class and script to proxy AJAX request accross domains.
It is intended to work around the cross-domain request restrictions found in
most browsers.

This proxy does not simply forward request and response bodies, it also forwards
cookies, user-agent strings, and content-types. This makes is extensively useful
for performing operations like ajax-based logins accross domains (which usually
rely on cookies).

Additionally, it will use cURL by default, and fall back to using the slower,
but native fopen() functionality when cURL isn't available.

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

    $proxy = new AjaxProxy('http://login.example.com/');
    $proxy->execute();

The first line created the proxy object. The proxy object performs the entirety
of the work. The AjaxProxy class' constructor takes 3 arguments, the last of
which is optional.

1. `$forward_host`, which is where all requests to the proxy will be routed.
2. `$allowed_hostname`, which an optional parameter. Is this is supplied, it
   should be a hostname or ip address that you would like to restrict requests
   to. Alternatively, it can be an array of hostnames or IPs. This way, you can
   make sure that only requests from certain clients ever access the proxy.
3. `$handle_errors`, which is a boolean flag with a default value of TRUE. If
   enabled, the object will use it's own error and exception handlers. This is
   useful if you plan to use proxy.php as a standalone script. If you are
   incorporating the class into a larger framework, although, you will likely
   want to specify false so it does not override any error and exception
   handling in your application.

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

When it comes to passing routes, always make sure to urlencode them. This is
especially true if there is a query string in your route. For example, suppose
you had a route that looks like:

    ajax/user/login?param_a=1&param_b=2

This is a **malformed** call to the proxy:

    http://proxy-location.com/proxy.php?route=ajax/user/login?param_a=1&param_b=2

This is a **well-formed** call to the proxy:

    http://proxy-location.com/proxy.php?route=ajax%2fuser%2flogin%3fparam_a%3d1%26param_b%3d2

## Dependencies

This class can use two different methods to make it's requests: cURL, and the
native fopen(). Since cURL is known to be quite a bit faster, the class first
checks for the availability of cURL, and will fallback to fopen() if needed.

At least one of these must be true:

1. fopen() requests are enabled via the `allow_url_fopen` option in
   php.ini. For more on that: [see the php manual](http://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen)
2. cURL is installed and loaded as a PHP module. The official docs are 
   [here](http://php.net/manual/en/book.curl.php),
   but sane people will probably do something like `$ sudo apt-get install php5-curl`

## Why Use ajax-proxy?

Okay, an ajax proxy isn't the most complicated bit to write. Most client-side
developers have had to write one before, and there are a number of examples on
the internet.

But proxy examples found online rarely handle the passing of cookies, and
almost every single one that is written in PHP uses cURL. If you are on a shared
host, and cURL isn't enabled, you're out of luck. But with ajax-proxy, both the
passing of headers and a fallback to fopen (non-cURL) are incorporated.
Additionally, it was written to be reused and extended.

ajax-proxy it written as a standalone class which can be used by itself or
incorporated into a larger framework. Accordingly, there are constructor options
to handle it's own errors and exceptions (standalone) or let the errors and
exceptions bubble up to the application.

Writing a proxy tends to be a quick-and-dirty thing that most client-side
developers don't want to spend more than a few hours writing. If you need cookie
or non-cURL support, some extra time will be tacked on for handling some of the
unexpected bugs and nuances of HTTP. ajax-proxy underwent 2 weeks of part-time
development with numerous feature additions and code reviews. Additionally, it's
fully documented with PHPDoc.

With ajax-proxy, you'll get a more powerful, rock-solid proxy and less time than
it would have taken you to write a basic one yourself.

Oh, and it's incredibly easy to use.

## Special Thanks

A special thanks several key players on the HUGE team that offered valuable
feedback, feature suggestions, and/or their time in a code review:

* Brett Mayen @ NY
* Daryl Bowden @ LA
* [Sankho Mallik](http://sankhomallik.com/) @ NY
* Martin Olsen @ NY
* [Patrick O'Neill](http://misteroneill.com/) @ NY
* [Tim McDuffie](http://www.tmcduffie.com/) @ NY
* [Sean O'Connor](http://seanoc.com) @ NY
* John Grogan @ NY

## Maintainer

Maintained by [Kenny Katzgrau](http://codefury.net) @ [HUGE](http://hugeinc.com)

## License

Copyright (c) 2010, HUGE LLC
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice,
  this list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

* Neither the name of HUGE LLC nor the names of its
  contributors may be used to endorse or promote products derived from this
  software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.