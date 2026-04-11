# Production Readiness Test Report

**Date**: 2026-04-08 21:14:10
**API**: https://api.sipanda.online
**Frontend**: https://sipanda.online


### 1. CORS Preflight (OPTIONS)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS /api/me → 204 No Content |
| ✅ PASS | Access-Control-Allow-Origin: https://sipanda.online |
| ✅ PASS | Access-Control-Allow-Credentials: true |
| ✅ PASS | Access-Control-Allow-Methods includes POST |
| ✅ PASS | No duplicate Access-Control-Allow-Origin headers |

### 2. CORS on Actual Responses
| Status | Detail |
|--------|--------|
| ✅ PASS | GET /api/me (no auth) → 401 Unauthorized |
| ✅ PASS | 401 response includes CORS headers |
| ✅ PASS | No duplicate CORS headers on 401 response |
| ✅ PASS | 401 returns JSON: {"message":"Unauthenticated."} |

### 3. Citizen Login
| Status | Detail |
|--------|--------|
| ❌ FAIL | Citizen login failed: {
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
} |
| ❌ FAIL | Login response missing user data |
| ⚠️ WARN | Bad login response: {
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
}{
    "message": "SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache` where `key` in (a8369aa19da85f54e883203ede3b6d7d505ac16b))",
    "exception": "Illuminate\Database\QueryException",
    "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
    "line": 825,
    "trace": [
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 979,
            "function": "runQueryCallback",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 958,
            "function": "tryAgainIfCausedByLostConnection",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 781,
            "function": "handleQueryException",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Connection.php",
            "line": 398,
            "function": "run",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3106,
            "function": "select",
            "class": "Illuminate\Database\Connection",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3091,
            "function": "runSelect",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3676,
            "function": "{closure:Illuminate\Database\Query\Builder::get():3090}",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php",
            "line": 3090,
            "function": "onceWithColumns",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 130,
            "function": "get",
            "class": "Illuminate\Database\Query\Builder",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php",
            "line": 105,
            "function": "many",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/Repository.php",
            "line": 117,
            "function": "get",
            "class": "Illuminate\Cache\DatabaseStore",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "get",
            "class": "Illuminate\Cache\Repository",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 301,
            "function": "{closure:Illuminate\Cache\RateLimiter::attempts():207}",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 207,
            "function": "withoutSerializationOrCompression",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php",
            "line": 130,
            "function": "attempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 154,
            "function": "tooManyAttempts",
            "class": "Illuminate\Cache\RateLimiter",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php",
            "line": 92,
            "function": "handleRequest",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Routing\Middleware\ThrottleRequests",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 807,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 786,
            "function": "runRouteWithinStack",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 750,
            "function": "runRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Routing/Router.php",
            "line": 739,
            "function": "dispatchToRoute",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 201,
            "function": "dispatch",
            "class": "Illuminate\Routing\Router",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 170,
            "function": "{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():198}",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:Illuminate\Pipeline\Pipeline::prepareDestination():168}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php",
            "line": 31,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php",
            "line": 21,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php",
            "line": 51,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TransformsRequest",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\TrimStrings",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php",
            "line": 27,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\ValidatePostSize",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php",
            "line": 110,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php",
            "line": 58,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\TrustProxies",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php",
            "line": 22,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/QueryStringToken.php",
            "line": 23,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\QueryStringToken",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/app/Http/Middleware/SecurityHeaders.php",
            "line": 18,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "App\Http\Middleware\SecurityHeaders",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php",
            "line": 62,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 209,
            "function": "handle",
            "class": "Illuminate\Http\Middleware\HandleCors",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php",
            "line": 127,
            "function": "{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 176,
            "function": "then",
            "class": "Illuminate\Pipeline\Pipeline",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php",
            "line": 145,
            "function": "sendRequestThroughRouter",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/vendor/laravel/framework/src/Illuminate/Foundation/Application.php",
            "line": 1220,
            "function": "handle",
            "class": "Illuminate\Foundation\Http\Kernel",
            "type": "->"
        },
        {
            "file": "/home/sipanda/retribusi-api/public/index.php",
            "line": 17,
            "function": "handleRequest",
            "class": "Illuminate\Foundation\Application",
            "type": "->"
        }
    ]
} |
| ❌ FAIL | Login response has 11 Access-Control-Allow-Origin headers (expected 1) |

### 4. Authenticated API Endpoints
| Status | Detail |
|--------|--------|
| ❌ FAIL | Skipping authenticated tests - no token |

### 5. Cross-Origin (admin.sipanda.online)
| Status | Detail |
|--------|--------|
| ✅ PASS | OPTIONS from admin.sipanda.online → 204 |
| ✅ PASS | CORS allows admin.sipanda.online |

### 6. Error Handling (No 500s)
| Status | Detail |
|--------|--------|
| ✅ PASS | Unknown endpoint returns 404 (not 500) |
| ✅ PASS | Health endpoint /up → 200 OK |

### 7. Frontend & PWA
| Status | Detail |
|--------|--------|
| ✅ PASS | Frontend sipanda.online → 200 OK |

## Summary

⚠️ **4 CRITICAL TEST(S) FAILED** — Needs attention.

- **Pass**: 14
- **Fail**: 4
- **Warn**: 1

