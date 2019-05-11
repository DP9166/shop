<?php

return [
    'alipay' => [
        'app_id'         => '2016093000633958',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtUrNY4CObHZ5VePjMPV6clBFTCpGeY66YmAkyWafCUwDel+qPv5QINaUHiGgFS5+puWkhy6jjIu17PWNnxV2nzHU9XRo1REf3YmgFLUhoUkkjauBMEGrqkVb+gQL5HLuIx9ARYn96oRQBHEuj+7wBambUOTIedalUx7r6cvZ72FW0ZfYZ80NiRiKr1VmsatWt1/DtByidaAbs5GBSegwfgaOFW2za3xzSJSFL8x/BloMORgWoZBiHXMXKi43pn058eKl6DblmuoSByWIZvVx9IVcHNIge5givcuLCxvyTL1sd55TPJyLz939W7oDTfLeiHQaZAekf3OMy8sCvjS3xQIDAQAB',
        'private_key'    => 'MIIEpAIBAAKCAQEA5qffKU2lH8ab0i0+2+PXjE7ljHRAmtCXVZSdxTU3cYhcM7XTKC6SbNjlUgEgH5QjHUARVT12dZCM2dUDcx5mogZLiDJ1gOAQPe6iEhDLHGgKsyknkQ0I98JF90FhfJZzJKXK3RdVUzi+Joznll2do2dtfeTni7jNJklDZ8YdWD1tMoVZ27W2bncMxw7IYgtozx4dDRtwJ/1yqajHgykV+WL+MeVDYhb3KLp2dI2qEE/7m9mMh2853Uv5dQms20ADOmhAUHFRbc9nevVkffLVq/BZSqO+3UBue955q02XY9aK0OkLzu2s8J7xtgGEzPOh3WH6c+J1aL4WJhdydAsLHQIDAQABAoIBAAvAY8tL7GzJBqlXqsszOYkpf+J/DceVdxBiKQOiKXf8VY5A5kg2zzkCd/SMoCFbv8o+uGNaZU2qFrMGek2EMABzR6fhwkDVqZ4uKU24U1DYMCtenmhyfJF68WuehiP92lH0rHhFNxCtZGq8ZRQxItXvcBGo9u8UdEdOFkQUQOx84+pOG5caIM3W3Fx8vmnrSNoQkZpv3DNlLbpoR7Ks67bomdiss2LV4cELwMDuHMIjQHPKIE+l/92LfykXQg8mHWpcFwHNGJ8sNBnQCl2wekFy+hZQxrbv5Q+0SY0yBijcwR2g3nAmoduDM3aQG84EzqJI6XWLtzxsFS3ehAB90GECgYEA/RS9rBcisqWaZvGUH3wM3BWi5yG5UUmkEPUQDJC5MC9KVC5TCkKRroYy4eDDdjHBMYIMlHMJ7i8lxl7wrEU/XucoSa10/t2lU9LHvb1HjKxE6/kNeHMXfhHKxv54SovxXNrNgW9xcZGY3yT68lyoEMwWOedN22KCSwv/HVvbsykCgYEA6VDquY8Ul/1UY/Kupj2moI4hdLYjlf5eM8/j72R5X+/ybLvX1aQ3HLVtms/VHRRNa/KAXkwv+IdTnE8H+FnhUUFsevoTD5wjYrO+nrkXPYD+V6zpOrTKGU9eggeQ4jRdjXZgicITuwPb5/TORK+T0O0khFQPv/ca7sHLhO+QatUCgYEA0sjLkOFUDObQypy6ed6f904t98OmzYVAGL+DNnPzSaaZibNrhkgbffhXuLmzEmYuOkXOpHWL981c8PNyEIk8VpbAf1zw7LU+Vapgoi9bwFZasQ8loQR6tI2tDkAzgCM+S9ARCZUAL07MvE3YlLOHZzYT7PYxal+JlWfQe2teeMECgYAz9KLwg14womcigq5FssDiTARDOzQdeLF9lDPL4XHt3T7826+qkZD8QaKQsOtiOF0tRqkzVn/wNiJ2UlsSAOHd+FWx5PJNrZVrq18tdUYpmgoJeCXPvuaqUDRZfFnVJZgXol7JDoDaSnez5Z3xSa/+/G//T8DaHKrQtDMf/UEJqQKBgQC+OamtMYaObhyNHgwcEvUHrhULLNlbgIFMsoCHE2GPVXj+Y6x2NoHy/RLwyTHBUt9O8A3JFrTXEwpQVdISrKxXsklL6KRAtgX3lG/qgmTer2RAXyK4Hgji9sKikm7stM+YzeZ7fFy25dzubQUqr++wIzfKZgf2YGnd16+Q5ZXSkw==',
        'log'            => [
        'file' => storage_path('logs/alipay.log'),
        ],
    ],

    'wechat' => [
        'app_id'      => '',
        'mch_id'      => '',
        'key'         => '',
        'cert_client' => '',
        'cert_key'    => '',
        'log'         => [
        'file' => storage_path('logs/wechat_pay.log'),
        ],
    ],
];