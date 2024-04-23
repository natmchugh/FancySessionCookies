# FancySessionCookies
PHP util to allow setting of partitioned cross site or third party cookies for PHP sessions

##Why might I need this package?
You want to implement a session in PHP with the partitioned attribute.

This package also adds the prefixes `__Secure` and `__Host` to the session cookie name when they are appropriate.

##How to use
The idea is to use the session_set_cookie_params() as you would normally in PHP to set nearly all the functionality of the library.

You can set a session cookie like this.
``
FancySessionCookies::startNewSession();
``
By default if the cookie could be set as partioned it will be.

Accessing a session is done using the default PHP methods e.g.
```
$_SESSION['last_access'] = time();

var_dump($_SESSION['last_access']);
// int(1713867921)

```
##Is this necessary?
No it should not be but currently partitioned cookies for sessions will require an RFC to be accepted into PHPs core functions. This package is designed as stop gap till the RFC is accepted and then all instances of `FancySessionCookies::startNewSession();` can be replaced with `session_start()`
