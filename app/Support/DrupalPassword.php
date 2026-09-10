<?php

namespace App\Support;

class DrupalPassword
{
    private const ITOA64 =
        './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private const HASH_LENGTH = 55;

    public static function check(string $password, ?string $storedHash): bool
    {
        if (! $storedHash) {
            return false;
        }

        // Drupal hashes upgraded from older versions.
        if (str_starts_with($storedHash, 'U$')) {
            $storedHash = substr($storedHash, 1);
            $password = md5($password);
        }

        if (str_starts_with($storedHash, '$S$')) {
            $computed = self::crypt('sha512', $password, $storedHash);
        } elseif (
            str_starts_with($storedHash, '$P$') ||
            str_starts_with($storedHash, '$H$')
        ) {
            $computed = self::crypt('md5', $password, $storedHash);
        } else {
            return false;
        }

        if ($computed === false) {
            return false;
        }

        return hash_equals($storedHash, $computed);
    }

    private static function crypt(
        string $algo,
        string $password,
        string $setting
    ): string|false {
        $setting = substr($setting, 0, 12);

        if (strlen($setting) < 12) {
            return false;
        }

        $countLog2 = strpos(self::ITOA64, $setting[3]);

        if (
            $countLog2 === false ||
            $countLog2 < 7 ||
            $countLog2 > 30
        ) {
            return false;
        }

        $salt = substr($setting, 4, 8);

        if (strlen($salt) !== 8) {
            return false;
        }

        $count = 1 << $countLog2;

        $hash = hash(
            $algo,
            $salt . $password,
            true
        );

        do {
            $hash = hash(
                $algo,
                $hash . $password,
                true
            );
        } while (--$count);

        $output =
            $setting .
            self::base64Encode($hash, strlen($hash));

        return substr($output, 0, self::HASH_LENGTH);
    }

    private static function base64Encode(
        string $input,
        int $count
    ): string {
        $output = '';
        $i = 0;

        do {
            $value = ord($input[$i++]);

            $output .= self::ITOA64[$value & 0x3f];

            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }

            $output .= self::ITOA64[($value >> 6) & 0x3f];

            if ($i++ >= $count) {
                break;
            }

            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }

            $output .= self::ITOA64[($value >> 12) & 0x3f];

            if ($i++ >= $count) {
                break;
            }

            $output .= self::ITOA64[($value >> 18) & 0x3f];

        } while ($i < $count);

        return $output;
    }
}
