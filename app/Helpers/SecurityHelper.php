<?php

namespace App\Helpers;

use Mews\Purifier\Facades\Purifier;

/**
 * Security Helper Class
 * 
 * Provides HTML sanitization methods to prevent XSS attacks.
 * All user-generated content should be sanitized before storage.
 */
class SecurityHelper
{
    /**
     * Sanitize HTML content - strips ALL HTML tags
     * 
     * Use for: Review comments, admin responses, user addresses, names, etc.
     * This is the most restrictive sanitization - no HTML allowed at all.
     *
     * @param string $input The input string to sanitize
     * @return string The sanitized string with all HTML removed
     */
    public static function sanitizeText(string $input): string
    {
        return Purifier::clean($input, [
            'HTML.Allowed' => '', // No HTML tags allowed
            'AutoFormat.RemoveEmpty' => true,
            'AutoFormat.Linkify' => false, // Don't auto-convert URLs to links
            'Core.Encoding' => 'UTF-8',
        ]);
    }
    
    /**
     * Sanitize with basic formatting allowed
     * 
     * Use for: Design feedback or content that may need basic formatting
     * Allows only safe tags: <p>, <br>, <strong>, <em>, <u>
     *
     * @param string $input The input string to sanitize
     * @return string The sanitized string with only safe HTML tags
     */
    public static function sanitizeWithBasicFormatting(string $input): string
    {
        return Purifier::clean($input, [
            'HTML.Allowed' => 'p,br,strong,em,u', // Only safe formatting tags
            'AutoFormat.RemoveEmpty' => true,
            'AutoFormat.Linkify' => false,
            'Core.Encoding' => 'UTF-8',
        ]);
    }
    
    /**
     * Sanitize URLs - ensures they are valid and safe
     * 
     * Use for: Google Maps links, website URLs, etc.
     * Validates and sanitizes URL format.
     *
     * @param string|null $url The URL to sanitize
     * @return string|null The sanitized URL or null if invalid
     */
    public static function sanitizeUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        
        // Remove any HTML/JavaScript injection attempts
        $cleanUrl = strip_tags($url);
        
        // Sanitize the URL
        $cleanUrl = filter_var($cleanUrl, FILTER_SANITIZE_URL);
        
        // Validate it's a proper URL
        return filter_var($cleanUrl, FILTER_VALIDATE_URL) ? $cleanUrl : null;
    }
    
    /**
     * Sanitize array of strings
     * 
     * Use for: Multiple text fields that need sanitization
     *
     * @param array $inputs Array of strings to sanitize
     * @return array Array of sanitized strings
     */
    public static function sanitizeArray(array $inputs): array
    {
        return array_map([self::class, 'sanitizeText'], $inputs);
    }
}
