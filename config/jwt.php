<?php

return [
    // 该JWT的签发者，是否使用是可选的；
    'iss' => "Online JWT Builder",
    // 接收该JWT的一方，是否使用是可选的；
    'aud' => "https://www.dscmall.cn",
    // 该JWT所面向的用户，是否使用是可选的；
    'sub' => "jrocket@example.com",
    // expires 什么时候过期，这里是一个Unix时间戳，是否使用是可选的；
    'exp' => 30, // 默认为 30 天
    // issued at 在什么时候签发的(UNIX时间)，是否使用是可选的；
    // 'iat' => 1356999524,
    // Not Before 如果当前时间在nbf里的时间之前，则Token不被接受；一般都会留一些余地，比如几分钟；，是否使用是可选的；
    // 'nbf' => 1357000000
];