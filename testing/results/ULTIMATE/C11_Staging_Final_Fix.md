spawn ssh -o StrictHostKeyChecking=no sipanda@157.10.252.74
sipanda@157.10.252.74's password: 
Welcome to Ubuntu 22.04.5 LTS (GNU/Linux 5.15.0-164-generic x86_64)

 * Panduan:  https://idcloudhost.com/panduan
 -------------------------------------------
 * Documentation:  https://help.ubuntu.com
 * Management:     https://landscape.canonical.com
 * Support:        https://ubuntu.com/advantage

 System information as of Wed Mar  4 09:43:59 UTC 2026

  System load:  1.0                Processes:             126
  Usage of /:   48.6% of 19.20GB   Users logged in:       0
  Memory usage: 35%                IPv4 address for ens3: 10.48.77.206
  Swap usage:   0%

 * Strictly confined Kubernetes makes edge and IoT secure. Learn how MicroK8s
   just raised the bar for easy, resilient and secure K8s cluster deployment.

   https://ubuntu.com/engage/secure-kubernetes-at-the-edge

Expanded Security Maintenance for Applications is not enabled.

11 updates can be applied immediately.
To see these additional updates run: apt list --upgradable

18 additional security updates can be applied with ESM Apps.
Learn more about enabling ESM Apps service at https://ubuntu.com/esm


The list of available updates is more than a week old.
To check for new updates run: sudo apt update

*** System restart required ***
Last login: Tue Apr 14 04:37:01 2026 from 180.251.145.115
-bash: /usr/lib/command-not-found: /usr/bin/python3: bad interpreter: No such file or directory
sipanda@sipanda:~$ <7.0.0.1/g' /home/sipanda/retribusi-api-staging/.env
sipanda@sipanda:~$ < artisan config:clear && php artisan migrate:status

   INFO  Configuration cache cleared successfully.  


   Illuminate\Database\QueryException 

  SQLSTATE[HY000] [1045] Access denied for user 'sipanda'@'localhost' (using password: YES) (Connection: mysql, SQL: select exists (select 1 from information_schema.tables where table_schema = 'retribusi_staging' and table_name = 'migrations' and table_type in ('BASE TABLE', 'SYSTEM VERSIONED')) as `exists`)

  at vendor/laravel/framework/src/Illuminate/Database/Connection.php:825
    [90m821[0m[90m▕ [0m[35;1m                    [0m[39;1m$this[0m[35;1m->[0m[39;1mgetName[0m[35;1m(), [0m[39;1m$query[0m[35;1m, [0m[39;1m$this[0m[35;1m->[0m[39;1mprepareBindings[0m[35;1m([0m[39;1m$bindings[0m[35;1m), [0m[39;1m$e[0m
    [90m822[0m[90m▕ [0m[39;1m                [0m[35;1m);[0m
    [90m823[0m[90m▕ [0m[35;1m            }[0m
    [90m824[0m[90m▕ [0m
[31;1m  ➜ [0m[3;1m825[0m[90m▕ [0m[35;1m            throw new [0m[39;1mQueryException[0m[35;1m([0m
    [90m826[0m[90m▕ [0m[35;1m                [0m[39;1m$this[0m[35;1m->[0m[39;1mgetName[0m[35;1m(), [0m[39;1m$query[0m[35;1m, [0m[39;1m$this[0m[35;1m->[0m[39;1mprepareBindings[0m[35;1m([0m[39;1m$bindings[0m[35;1m), [0m[39;1m$e[0m
    [90m827[0m[90m▕ [0m[39;1m            [0m[35;1m);[0m
    [90m828[0m[90m▕ [0m[35;1m        }[0m
    [90m829[0m[90m▕ [0m[35;1m    }[0m

      [2m+34 vendor frames [22m

  35  artisan:13
      Illuminate\Foundation\Application::handleCommand()

sipanda@sipanda:~/retribusi-api-staging$ exit
logout
Connection to 157.10.252.74 closed.
