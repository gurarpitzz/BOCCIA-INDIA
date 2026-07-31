<?php
// includes/constants.php - Centralized constants for BSFI dropdown options and validation lists

if (!defined('BSFI_CONSTANTS_LOADED')) {
    define('BSFI_CONSTANTS_LOADED', true);

    // Event Levels
    define('EVENT_LEVELS', [
        'State',
        'National',
        'International'
    ]);

    // Athlete Classifications
    define('CLASSIFICATIONS', [
        'BC1',
        'BC2',
        'BC3',
        'BC4'
    ]);

    // Rank / Result Options
    define('RESULT_OPTIONS', [
        'Gold',
        'Silver',
        'Bronze',
        '4th',
        '5th',
        'Participation',
        'Other'
    ]);

    // Standard list of all 36 Indian States and Union Territories
    define('INDIAN_STATES', [
        'Andhra Pradesh',
        'Arunachal Pradesh',
        'Assam',
        'Bihar',
        'Chhattisgarh',
        'Goa',
        'Gujarat',
        'Haryana',
        'Himachal Pradesh',
        'Jharkhand',
        'Karnataka',
        'Kerala',
        'Madhya Pradesh',
        'Maharashtra',
        'Manipur',
        'Meghalaya',
        'Mizoram',
        'Nagaland',
        'Odisha',
        'Punjab',
        'Rajasthan',
        'Sikkim',
        'Tamil Nadu',
        'Telangana',
        'Tripura',
        'Uttar Pradesh',
        'Uttarakhand',
        'West Bengal',
        'Andaman and Nicobar Islands',
        'Chandigarh',
        'Dadra and Nagar Haveli and Daman and Diu',
        'Delhi',
        'Jammu and Kashmir',
        'Ladakh',
        'Lakshadweep',
        'Puducherry'
    ]);
}
