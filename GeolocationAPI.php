<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'application/third_party/geoip2/geoip2.phar';
use GeoIp2\Database\Reader;

// Maxmind GeoLite2
// Database docs: https://maxmind.github.io/GeoIP2-php/
// Database downloads (GeoLite2-City): https://www.maxmind.com/en/accounts/294478/geoip/downloads

class Geolocation {

  protected $CI;

  public function __construct() {
    $this->CI =& get_instance();
  }

  public function getUserCity() {
    $location = $this->geolocate();

    if ($location) return $location->city->name;
    // error_log('No location for city.');
    return FALSE;
  }

  public function getUserStateName() {
    $location = $this->geolocate();

    if ($location) return $location->mostSpecificSubdivision->name;
    // error_log('No location for state name.');
    return FALSE;
  }

  public function getUserStateCode() {
    $location = $this->geolocate();

    if ($location) return $location->mostSpecificSubdivision->isoCode;
    // error_log('No location for state code.');
    return FALSE;
  }

  public function getUserPostalCode() {
    $location = $this->geolocate();

    if ($location) return $location->postal->code;
    // error_log('No location for postal code.');
    return FALSE;
  }

  public function getUserCityAndState() {
    $location = $this->geolocate();

    if ($location) return $location->city->name . ', ' . $location->mostSpecificSubdivision->isoCode;
    // error_log('No location for city and state.');
    return FALSE;
  }

  public function getUserCountryName() {
    $location = $this->geolocate();

    if ($location) return $location->country->name;
    // error_log('No location for country name.');
    return 'unknown';
  }

  public function getUserAddressName() {
    $location = $this->geolocate();

    if ($location) return $location->city->name . ', ' . $location->mostSpecificSubdivision->isoCode . ", " .$location->country->name;
    // error_log('No location for country name.');
    return 'unknown';
  }

  public function getUserCountryCode() {
    $location = $this->geolocate();

    if ($location) return $location->country->isoCode;
    // error_log('No location for country code.');
    return FALSE;
  }

  public function getUserTimezone() {
    $location = $this->geolocate();

    if ($location) return $location->location->timeZone;
    // error_log('No timezone, using default.');

    $this->CI->load->model('Parameters');
    $parameter = $this->CI->Parameters->getByName('default_timezone');
    if ($parameter) return $parameter->value;
    return 'America/New_York';
  }

  public function getUserTimezoneAbbreviated() {
      $user_timezone = new DateTime('now', new DateTimeZone($this->getUserTimezone()));
      return $user_timezone->format('T');
  }

  public function getUserIp() {
    $userIP = "";

    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
      // echo 'HTTP_CF_CONNECTING_IP: ' . $_SERVER['HTTP_CF_CONNECTING_IP'] . '<br>';
      $userIP = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
      // echo 'HTTP_CLIENT_IP: ' . $_SERVER['HTTP_CLIENT_IP'] . '<br>';
      $userIP = $_SERVER['HTTP_CLIENT_IP'];
    }
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      // echo 'HTTP_X_FORWARDED_FOR: ' . $_SERVER['HTTP_X_FORWARDED_FOR'] . '<br>';
      $userIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
      // echo 'HTTP_X_FORWARDED: ' . $_SERVER['HTTP_X_FORWARDED'] . '<br>';
      $userIP = $_SERVER['HTTP_X_FORWARDED'];
    }
    else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
      // echo 'HTTP_FORWARDED_FOR: ' . $_SERVER['HTTP_FORWARDED_FOR'] . '<br>';
      $userIP = $_SERVER['HTTP_FORWARDED_FOR'];
    }
    else if (isset($_SERVER['HTTP_FORWARDED'])) {
      // echo 'HTTP_FORWARDED: ' . $_SERVER['HTTP_FORWARDED'] . '<br>';
      $userIP = $_SERVER['HTTP_FORWARDED'];
    }
    else if (isset($_SERVER['REMOTE_ADDR'])) {
      // echo 'REMOTE_ADDR: ' . $_SERVER['REMOTE_ADDR'] . '<br>';
      $userIP = $_SERVER['REMOTE_ADDR'];
    }

    return $userIP;
  }

  public function getUserLatitude() {
    $location = $this->geolocate();

    if ($location) return $location->location->latitude;
    // error_log('No latitude.');
  }

  public function getUserLongitude() {
    $location = $this->geolocate();

    if ($location) return $location->location->longitude;
    // error_log('No longitude.');
  }

  public function geolocate($userIP = FALSE) {

    if (!$userIP) $userIP = $this->getUserIp();

    // if we have a valid user IP address, use it to geolocate the user
    if ( filter_var($userIP, FILTER_VALIDATE_IP) ) {
      $reader = new Reader('application/third_party/geoip2/GeoLite2-City.mmdb');

      try {
        $record = $reader->city($userIP);
      }
      catch (\Exception $e) {
        $record = FALSE;
        // error_log('Unable to locate IP: ' . $userIP);
      }
    }
    else {
      $record = FALSE;
      // error_log('Invalid IP: ' . $userIP);
    }

    return $record;
  }

  // Returns an associative array with a full list of timezones and their user-friendly display values
  public function getTimezones() {
    $timezones = array(
      ['label' => '(GMT-08:00) Pacific Time (US & Canada)', 'value' => 'America/Los_Angeles'],
      ['label' => '(GMT-07:00) Mountain Time (US & Canada)', 'value' => 'America/Denver'],
      ['label' => '(GMT-06:00) Central Time (US & Canada)', 'value' => 'America/Chicago'],
      ['label' => '(GMT-05:00) Eastern Time (US & Canada)', 'value' => 'America/New_York'],
      ['label' => '(GMT-12:00) International Date Line West', 'value' => 'Etc/GMT+12'],
      ['label' => '(GMT-11:00) Midway Island, Samoa', 'value' => 'Pacific/Midway'],
      ['label' => '(GMT-10:00) Hawaii', 'value' => 'Pacific/Honolulu'],
      ['label' => '(GMT-09:00) Alaska', 'value' => 'America/Anchorage'],
      ['label' => '(GMT-08:00) Tijuana, Baja California', 'value' => 'America/Tijuana'],
      ['label' => '(GMT-07:00) Arizona', 'value' => 'America/Phoenix'],
      ['label' => '(GMT-07:00) Chihuahua, La Paz, Mazatlan', 'value' => 'America/Chihuahua'],
      ['label' => '(GMT-06:00) Central America', 'value' => 'America/Managua'],
      ['label' => '(GMT-06:00) Guadalajara, Mexico City, Monterrey', 'value' => 'America/Mexico_City'],
      ['label' => '(GMT-06:00) Saskatchewan', 'value' => 'Canada/Saskatchewan'],
      ['label' => '(GMT-05:00) Bogota, Lima, Quito, Rio Branco', 'value' => 'America/Bogota'],
      ['label' => '(GMT-05:00) Indiana (East)', 'value' => 'America/Indiana/Indianapolis'],
      ['label' => '(GMT-04:00) Atlantic Time (Canada)', 'value' => 'Canada/Atlantic'],
      ['label' => '(GMT-04:00) Caracas, La Paz', 'value' => 'America/Caracas'],
      ['label' => '(GMT-04:00) Manaus', 'value' => 'America/Manaus'],
      ['label' => '(GMT-04:00) Santiago', 'value' => 'America/Santiago'],
      ['label' => '(GMT-03:30) Newfoundland', 'value' => 'Canada/Newfoundland'],
      ['label' => '(GMT-03:00) Brasilia', 'value' => 'America/Sao_Paulo'],
      ['label' => '(GMT-03:00) Buenos Aires, Georgetown', 'value' => 'America/Argentina/Buenos_Aires'],
      ['label' => '(GMT-03:00) Greenland', 'value' => 'America/Godthab'],
      ['label' => '(GMT-03:00) Montevideo', 'value' => 'America/Montevideo'],
      ['label' => '(GMT-02:00) Mid-Atlantic', 'value' => 'America/Noronha'],
      ['label' => '(GMT-01:00) Cape Verde Is.', 'value' => 'Atlantic/Cape_Verde'],
      ['label' => '(GMT-01:00) Azores', 'value' => 'Atlantic/Azores'],
      ['label' => '(GMT+00:00) Casablanca, Monrovia, Reykjavik', 'value' => 'Africa/Casablanca'],
      ['label' => '(GMT+00:00) Greenwich Mean Time: Dublin, Edinburgh, Lisbon, London', 'value' => 'Etc/Greenwich'],
      ['label' => '(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna', 'value' => 'Europe/Amsterdam'],
      ['label' => '(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague', 'value' => 'Europe/Belgrade'],
      ['label' => '(GMT+01:00) Brussels, Copenhagen, Madrid, Paris', 'value' => 'Europe/Brussels'],
      ['label' => '(GMT+01:00) Sarajevo, Skopje, Warsaw, Zagreb', 'value' => 'Europe/Sarajevo'],
      ['label' => '(GMT+01:00) West Central Africa', 'value' => 'Africa/Lagos'],
      ['label' => '(GMT+02:00) Amman', 'value' => 'Asia/Amman'],
      ['label' => '(GMT+02:00) Athens, Bucharest, Istanbul', 'value' => 'Europe/Athens'],
      ['label' => '(GMT+02:00) Beirut', 'value' => 'Asia/Beirut'],
      ['label' => '(GMT+02:00) Cairo', 'value' => 'Africa/Cairo'],
      ['label' => '(GMT+02:00) Harare, Pretoria', 'value' => 'Africa/Harare'],
      ['label' => '(GMT+02:00) Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius', 'value' => 'Europe/Helsinki'],
      ['label' => '(GMT+02:00) Jerusalem', 'value' => 'Asia/Jerusalem'],
      ['label' => '(GMT+02:00) Minsk', 'value' => 'Europe/Minsk'],
      ['label' => '(GMT+02:00) Windhoek', 'value' => 'Africa/Windhoek'],
      ['label' => '(GMT+03:00) Kuwait, Riyadh, Baghdad', 'value' => 'Asia/Kuwait'],
      ['label' => '(GMT+03:00) Moscow, St. Petersburg, Volgograd', 'value' => 'Europe/Moscow'],
      ['label' => '(GMT+03:00) Nairobi', 'value' => 'Africa/Nairobi'],
      ['label' => '(GMT+03:00) Tbilisi', 'value' => 'Asia/Tbilisi'],
      ['label' => '(GMT+03:30) Tehran', 'value' => 'Asia/Tehran'],
      ['label' => '(GMT+04:00) Abu Dhabi, Muscat', 'value' => 'Asia/Muscat'],
      ['label' => '(GMT+04:00) Baku', 'value' => 'Asia/Baku'],
      ['label' => '(GMT+04:00) Yerevan', 'value' => 'Asia/Yerevan'],
      ['label' => '(GMT+04:30) Kabul', 'value' => 'Asia/Kabul'],
      ['label' => '(GMT+05:00) Yekaterinburg', 'value' => 'Asia/Yekaterinburg'],
      ['label' => '(GMT+05:00) Islamabad, Karachi, Tashkent', 'value' => 'Asia/Karachi'],
      ['label' => '(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi', 'value' => 'Asia/Calcutta'],
      ['label' => '(GMT+05:30) Sri Jayawardenapura', 'value' => 'Asia/Calcutta'],
      ['label' => '(GMT+05:45) Kathmandu', 'value' => 'Asia/Katmandu'],
      ['label' => '(GMT+06:00) Almaty, Novosibirsk', 'value' => 'Asia/Almaty'],
      ['label' => '(GMT+06:00) Astana, Dhaka', 'value' => 'Asia/Dhaka'],
      ['label' => '(GMT+06:30) Yangon (Rangoon)', 'value' => 'Asia/Rangoon'],
      ['label' => '(GMT+07:00) Bangkok, Hanoi, Jakarta', 'value' => 'Asia/Bangkok'],
      ['label' => '(GMT+07:00) Krasnoyarsk', 'value' => 'Asia/Krasnoyarsk'],
      ['label' => '(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi', 'value' => 'Asia/Hong_Kong'],
      ['label' => '(GMT+08:00) Kuala Lumpur, Singapore', 'value' => 'Asia/Kuala_Lumpur'],
      ['label' => '(GMT+08:00) Irkutsk, Ulaan Bataar', 'value' => 'Asia/Irkutsk'],
      ['label' => '(GMT+08:00) Perth', 'value' => 'Australia/Perth'],
      ['label' => '(GMT+08:00) Taipei', 'value' => 'Asia/Taipei'],
      ['label' => '(GMT+09:00) Osaka, Sapporo, Tokyo', 'value' => 'Asia/Tokyo'],
      ['label' => '(GMT+09:00) Seoul', 'value' => 'Asia/Seoul'],
      ['label' => '(GMT+09:00) Yakutsk', 'value' => 'Asia/Yakutsk'],
      ['label' => '(GMT+09:30) Adelaide', 'value' => 'Australia/Adelaide'],
      ['label' => '(GMT+09:30) Darwin', 'value' => 'Australia/Darwin'],
      ['label' => '(GMT+10:00) Brisbane', 'value' => 'Australia/Brisbane'],
      ['label' => '(GMT+10:00) Canberra, Melbourne, Sydney', 'value' => 'Australia/Canberra'],
      ['label' => '(GMT+10:00) Hobart', 'value' => 'Australia/Hobart'],
      ['label' => '(GMT+10:00) Guam, Port Moresby', 'value' => 'Pacific/Guam'],
      ['label' => '(GMT+10:00) Vladivostok', 'value' => 'Asia/Vladivostok'],
      ['label' => '(GMT+11:00) Magadan, Solomon Is., New Caledonia', 'value' => 'Asia/Magadan'],
      ['label' => '(GMT+12:00) Auckland, Wellington', 'value' => 'Pacific/Auckland'],
      ['label' => '(GMT+12:00) Fiji, Kamchatka, Marshall Is.', 'value' => 'Pacific/Fiji'],
      ['label' => '(GMT+13:00) Nuku\'alofa', 'value' => 'Pacific/Tongatapu']
    );

    return $timezones;
  }

  // Returns an associative array with a full list of countries
  public function getCountries(){
    $countries = array(
      ["value" =>"AF", "label" => "Afghanistan"],
      ["value" =>"AL", "label" => "Albania"],
      ["value" =>"DZ", "label" => "Algeria"],
      ["value" =>"AS", "label" => "American Samoa"],
      ["value" =>"AD", "label" => "Andorra"],
      ["value" =>"AO", "label" => "Angola"],
      ["value" =>"AI", "label" => "Anguilla"],
      ["value" =>"AQ", "label" => "Antarctica"],
      ["value" =>"AG", "label" => "Antigua and Barbuda"],
      ["value" =>"AR", "label" => "Argentina"],
      ["value" =>"AM", "label" => "Armenia"],
      ["value" =>"AW", "label" => "Aruba"],
      ["value" =>"AU", "label" => "Australia"],
      ["value" =>"AT", "label" => "Austria"],
      ["value" =>"AZ", "label" => "Azerbaijan"],
      ["value" =>"BS", "label" => "Bahamas"],
      ["value" =>"BH", "label" => "Bahrain"],
      ["value" =>"BD", "label" => "Bangladesh"],
      ["value" =>"BB", "label" => "Barbados"],
      ["value" =>"BY", "label" => "Belarus"],
      ["value" =>"BE", "label" => "Belgium"],
      ["value" =>"BZ", "label" => "Belize"],
      ["value" =>"BJ", "label" => "Benin"],
      ["value" =>"BM", "label" => "Bermuda"],
      ["value" =>"BT", "label" => "Bhutan"],
      ["value" =>"BO", "label" => "Bolivia"],
      ["value" =>"BA", "label" => "Bosnia and Herzegowina"],
      ["value" =>"BW", "label" => "Botswana"],
      ["value" =>"BV", "label" => "Bouvet Island"],
      ["value" =>"BR", "label" => "Brazil"],
      ["value" =>"IO", "label" => "British Indian Ocean Territory"],
      ["value" =>"BN", "label" => "Brunei Darussalam"],
      ["value" =>"BG", "label" => "Bulgaria"],
      ["value" =>"BF", "label" => "Burkina Faso"],
      ["value" =>"BI", "label" => "Burundi"],
      ["value" =>"KH", "label" => "Cambodia"],
      ["value" =>"CM", "label" => "Cameroon"],
      ["value" =>"CA", "label" => "Canada"],
      ["value" =>"CV", "label" => "Cape Verde"],
      ["value" =>"KY", "label" => "Cayman Islands"],
      ["value" =>"CF", "label" => "Central African Republic"],
      ["value" =>"TD", "label" => "Chad"],
      ["value" =>"CL", "label" => "Chile"],
      ["value" =>"CN", "label" => "China"],
      ["value" =>"CX", "label" => "Christmas Island"],
      ["value" =>"CC", "label" => "Cocos (Keeling) Islands"],
      ["value" =>"CO", "label" => "Colombia"],
      ["value" =>"KM", "label" => "Comoros"],
      ["value" =>"CG", "label" => "Congo"],
      ["value" =>"CK", "label" => "Cook Islands"],
      ["value" =>"CR", "label" => "Costa Rica"],
      ["value" =>"CI", "label" => "Cote D'Ivoire"],
      ["value" =>"HR", "label" => "Croatia"],
      ["value" =>"CU", "label" => "Cuba"],
      ["value" =>"CY", "label" => "Cyprus"],
      ["value" =>"CZ", "label" => "Czech Republic"],
      ["value" =>"DK", "label" => "Denmark"],
      ["value" =>"DJ", "label" => "Djibouti"],
      ["value" =>"DM", "label" => "Dominica"],
      ["value" =>"DO", "label" => "Dominican Republic"],
      ["value" =>"TL", "label" => "East Timor"],
      ["value" =>"EC", "label" => "Ecuador"],
      ["value" =>"EG", "label" => "Egypt"],
      ["value" =>"SV", "label" => "El Salvador"],
      ["value" =>"GQ", "label" => "Equatorial Guinea"],
      ["value" =>"ER", "label" => "Eritrea"],
      ["value" =>"EE", "label" => "Estonia"],
      ["value" =>"ET", "label" => "Ethiopia"],
      ["value" =>"FK", "label" => "Falkland Islands (Malvinas)"],
      ["value" =>"FO", "label" => "Faroe Islands"],
      ["value" =>"FJ", "label" => "Fiji"],
      ["value" =>"FI", "label" => "Finland"],
      ["value" =>"FR", "label" => "France"],
      ["value" =>"FX", "label" => "France, Metropolitan"],
      ["value" =>"GF", "label" => "French Guiana"],
      ["value" =>"PF", "label" => "French Polynesia"],
      ["value" =>"TF", "label" => "French Southern Territories"],
      ["value" =>"GA", "label" => "Gabon"],
      ["value" =>"GM", "label" => "Gambia"],
      ["value" =>"GE", "label" => "Georgia"],
      ["value" =>"DE", "label" => "Germany"],
      ["value" =>"GH", "label" => "Ghana"],
      ["value" =>"GI", "label" => "Gibraltar"],
      ["value" =>"GR", "label" => "Greece"],
      ["value" =>"GL", "label" => "Greenland"],
      ["value" =>"GD", "label" => "Grenada"],
      ["value" =>"GP", "label" => "Guadeloupe"],
      ["value" =>"GU", "label" => "Guam"],
      ["value" =>"GT", "label" => "Guatemala"],
      ["value" =>"GN", "label" => "Guinea"],
      ["value" =>"GW", "label" => "Guinea-bissau"],
      ["value" =>"GY", "label" => "Guyana"],
      ["value" =>"HT", "label" => "Haiti"],
      ["value" =>"HM", "label" => "Heard and Mc Donald Islands"],
      ["value" =>"HN", "label" => "Honduras"],
      ["value" =>"HK", "label" => "Hong Kong"],
      ["value" =>"HU", "label" => "Hungary"],
      ["value" =>"IS", "label" => "Iceland"],
      ["value" =>"IN", "label" => "India"],
      ["value" =>"ID", "label" => "Indonesia"],
      ["value" =>"IR", "label" => "Iran (Islamic Republic of)"],
      ["value" =>"IQ", "label" => "Iraq"],
      ["value" =>"IE", "label" => "Ireland"],
      ["value" =>"IL", "label" => "Israel"],
      ["value" =>"IT", "label" => "Italy"],
      ["value" =>"JM", "label" => "Jamaica"],
      ["value" =>"JP", "label" => "Japan"],
      ["value" =>"JO", "label" => "Jordan"],
      ["value" =>"KZ", "label" => "Kazakhstan"],
      ["value" =>"KE", "label" => "Kenya"],
      ["value" =>"KI", "label" => "Kiribati"],
      ["value" =>"KP", "label" => "Korea, Democratic People's Republic of"],
      ["value" =>"KR", "label" => "Korea, Republic of"],
      ["value" =>"KW", "label" => "Kuwait"],
      ["value" =>"KG", "label" => "Kyrgyzstan"],
      ["value" =>"LA", "label" => "Lao People's Democratic Republic"],
      ["value" =>"LV", "label" => "Latvia"],
      ["value" =>"LB", "label" => "Lebanon"],
      ["value" =>"LS", "label" => "Lesotho"],
      ["value" =>"LR", "label" => "Liberia"],
      ["value" =>"LY", "label" => "Libyan Arab Jamahiriya"],
      ["value" =>"LI", "label" => "Liechtenstein"],
      ["value" =>"LT", "label" => "Lithuania"],
      ["value" =>"LU", "label" => "Luxembourg"],
      ["value" =>"MO", "label" => "Macau"],
      ["value" =>"MK", "label" => "Macedonia, The Former Yugoslav Republic of"],
      ["value" =>"MG", "label" => "Madagascar"],
      ["value" =>"MW", "label" => "Malawi"],
      ["value" =>"MY", "label" => "Malaysia"],
      ["value" =>"MV", "label" => "Maldives"],
      ["value" =>"ML", "label" => "Mali"],
      ["value" =>"MT", "label" => "Malta"],
      ["value" =>"MH", "label" => "Marshall Islands"],
      ["value" =>"MQ", "label" => "Martinique"],
      ["value" =>"MR", "label" => "Mauritania"],
      ["value" =>"MU", "label" => "Mauritius"],
      ["value" =>"YT", "label" => "Mayotte"],
      ["value" =>"MX", "label" => "Mexico"],
      ["value" =>"FM", "label" => "Micronesia, Federated States of"],
      ["value" =>"MD", "label" => "Moldova, Republic of"],
      ["value" =>"MC", "label" => "Monaco"],
      ["value" =>"MN", "label" => "Mongolia"],
      ["value" =>"MS", "label" => "Montserrat"],
      ["value" =>"MA", "label" => "Morocco"],
      ["value" =>"MZ", "label" => "Mozambique"],
      ["value" =>"MM", "label" => "Myanmar"],
      ["value" =>"NA", "label" => "Namibia"],
      ["value" =>"NR", "label" => "Nauru"],
      ["value" =>"NP", "label" => "Nepal"],
      ["value" =>"NL", "label" => "Netherlands"],
      ["value" =>"AN", "label" => "Netherlands Antilles"],
      ["value" =>"NC", "label" => "New Caledonia"],
      ["value" =>"NZ", "label" => "New Zealand"],
      ["value" =>"NI", "label" => "Nicaragua"],
      ["value" =>"NE", "label" => "Niger"],
      ["value" =>"NG", "label" => "Nigeria"],
      ["value" =>"NU", "label" => "Niue"],
      ["value" =>"NF", "label" => "Norfolk Island"],
      ["value" =>"MP", "label" => "Northern Mariana Islands"],
      ["value" =>"NO", "label" => "Norway"],
      ["value" =>"OM", "label" => "Oman"],
      ["value" =>"PK", "label" => "Pakistan"],
      ["value" =>"PW", "label" => "Palau"],
      ["value" =>"PA", "label" => "Panama"],
      ["value" =>"PG", "label" => "Papua New Guinea"],
      ["value" =>"PY", "label" => "Paraguay"],
      ["value" =>"PE", "label" => "Peru"],
      ["value" =>"PH", "label" => "Philippines"],
      ["value" =>"PN", "label" => "Pitcairn"],
      ["value" =>"PL", "label" => "Poland"],
      ["value" =>"PT", "label" => "Portugal"],
      ["value" =>"PR", "label" => "Puerto Rico"],
      ["value" =>"QA", "label" => "Qatar"],
      ["value" =>"RE", "label" => "Reunion"],
      ["value" =>"RO", "label" => "Romania"],
      ["value" =>"RU", "label" => "Russian Federation"],
      ["value" =>"RW", "label" => "Rwanda"],
      ["value" =>"KN", "label" => "Saint Kitts and Nevis"],
      ["value" =>"LC", "label" => "Saint Lucia"],
      ["value" =>"VC", "label" => "Saint Vincent and the Grenadines"],
      ["value" =>"WS", "label" => "Samoa"],
      ["value" =>"SM", "label" => "San Marino"],
      ["value" =>"ST", "label" => "Sao Tome and Principe"],
      ["value" =>"SA", "label" => "Saudi Arabia"],
      ["value" =>"SN", "label" => "Senegal"],
      ["value" =>"SC", "label" => "Seychelles"],
      ["value" =>"SL", "label" => "Sierra Leone"],
      ["value" =>"SG", "label" => "Singapore"],
      ["value" =>"SK", "label" => "Slovakia (Slovak Republic)"],
      ["value" =>"SI", "label" => "Slovenia"],
      ["value" =>"SB", "label" => "Solomon Islands"],
      ["value" =>"SO", "label" => "Somalia"],
      ["value" =>"ZA", "label" => "South Africa"],
      ["value" =>"GS", "label" => "South Georgia and the South Sandwich Islands"],
      ["value" =>"ES", "label" => "Spain"],
      ["value" =>"LK", "label" => "Sri Lanka"],
      ["value" =>"SH", "label" => "St. Helena"],
      ["value" =>"PM", "label" => "St. Pierre and Miquelon"],
      ["value" =>"SD", "label" => "Sudan"],
      ["value" =>"SR", "label" => "Suriname"],
      ["value" =>"SJ", "label" => "Svalbard and Jan Mayen Islands"],
      ["value" =>"SZ", "label" => "Swaziland"],
      ["value" =>"SE", "label" => "Sweden"],
      ["value" =>"CH", "label" => "Switzerland"],
      ["value" =>"SY", "label" => "Syrian Arab Republic"],
      ["value" =>"TW", "label" => "Taiwan"],
      ["value" =>"TJ", "label" => "Tajikistan"],
      ["value" =>"TZ", "label" => "Tanzania, United Republic of"],
      ["value" =>"TH", "label" => "Thailand"],
      ["value" =>"TG", "label" => "Togo"],
      ["value" =>"TK", "label" => "Tokelau"],
      ["value" =>"TO", "label" => "Tonga"],
      ["value" =>"TT", "label" => "Trinidad and Tobago"],
      ["value" =>"TN", "label" => "Tunisia"],
      ["value" =>"TR", "label" => "Turkey"],
      ["value" =>"TM", "label" => "Turkmenistan"],
      ["value" =>"TC", "label" => "Turks and Caicos Islands"],
      ["value" =>"TV", "label" => "Tuvalu"],
      ["value" =>"UG", "label" => "Uganda"],
      ["value" =>"UA", "label" => "Ukraine"],
      ["value" =>"AE", "label" => "United Arab Emirates"],
      ["value" =>"GB", "label" => "United Kingdom"],
      ["value" =>"US", "label" => "United States"],
      ["value" =>"UM", "label" => "United States Minor Outlying Islands"],
      ["value" =>"UY", "label" => "Uruguay"],
      ["value" =>"UZ", "label" => "Uzbekistan"],
      ["value" =>"VU", "label" => "Vanuatu"],
      ["value" =>"VA", "label" => "Vatican City State (Holy See)"],
      ["value" =>"VE", "label" => "Venezuela"],
      ["value" =>"VN", "label" => "Viet Nam"],
      ["value" =>"VG", "label" => "Virgin Islands (British)"],
      ["value" =>"VI", "label" => "Virgin Islands (U.S.)"],
      ["value" =>"WF", "label" => "Wallis and Futuna Islands"],
      ["value" =>"EH", "label" => "Western Sahara"],
      ["value" =>"YE", "label" => "Yemen"],
      ["value" =>"RS", "label" => "Serbia"],
      ["value" =>"CD", "label" => "The Democratic Republic of Congo"],
      ["value" =>"ZM", "label" => "Zambia"],
      ["value" =>"ZW", "label" => "Zimbabwe"],
      ["value" =>"JE", "label" => "Jersey"],
      ["value" =>"BL", "label" => "St. Barthelemy"],
      ["value" =>"XU", "label" => "St. Eustatius"],
      ["value" =>"XC", "label" => "Canary Islands"],
      ["value" =>"ME", "label" => "Montenegro"]
    );

    return $countries;
  }

  // Returns an associative array with a full list of languages
  public function getLanguages() {
    $languages = [
      ['value'=>'ab', 'label' => 'Abkhazian'],
      ['value'=>'aa', 'label' => 'Afar'],
      ['value'=>'af', 'label' => 'Afrikaans'],
      ['value'=>'ak', 'label' => 'Akan'],
      ['value'=>'sq', 'label' => 'Albanian'],
      ['value'=>'am', 'label' => 'Amharic'],
      ['value'=>'ar', 'label' => 'Arabic'],
      ['value'=>'an', 'label' => 'Aragonese'],
      ['value'=>'hy', 'label' => 'Armenian'],
      ['value'=>'as', 'label' => 'Assamese'],
      ['value'=>'av', 'label' => 'Avaric'],
      ['value'=>'ae', 'label' => 'Avestan'],
      ['value'=>'ay', 'label' => 'Aymara'],
      ['value'=>'az', 'label' => 'Azerbaijani'],
      ['value'=>'bm', 'label' => 'Bambara'],
      ['value'=>'ba', 'label' => 'Bashkir'],
      ['value'=>'eu', 'label' => 'Basque'],
      ['value'=>'be', 'label' => 'Belarusian'],
      ['value'=>'bn', 'label' => 'Bengali'],
      ['value'=>'bh', 'label' => 'Bihari languages'],
      ['value'=>'bi', 'label' => 'Bislama'],
      ['value'=>'bs', 'label' => 'Bosnian'],
      ['value'=>'br', 'label' => 'Breton'],
      ['value'=>'bg', 'label' => 'Bulgarian'],
      ['value'=>'my', 'label' => 'Burmese'],
      ['value'=>'ca', 'label' => 'Catalan, Valencian'],
      ['value'=>'km', 'label' => 'Central Khmer'],
      ['value'=>'ch', 'label' => 'Chamorro'],
      ['value'=>'ce', 'label' => 'Chechen'],
      ['value'=>'ny', 'label' => 'Chichewa, Chewa, Nyanja'],
      ['value'=>'zh', 'label' => 'Chinese'],
      ['value'=>'cu', 'label' => 'Church Slavonic, Old Bulgarian, Old Church Slavonic'],
      ['value'=>'cv', 'label' => 'Chuvash'],
      ['value'=>'kw', 'label' => 'Cornish'],
      ['value'=>'co', 'label' => 'Corsican'],
      ['value'=>'cr', 'label' => 'Cree'],
      ['value'=>'hr', 'label' => 'Croatian'],
      ['value'=>'cs', 'label' => 'Czech'],
      ['value'=>'da', 'label' => 'Danish'],
      ['value'=>'dv', 'label' => 'Divehi, Dhivehi, Maldivian'],
      ['value'=>'nl', 'label' => 'Dutch, Flemish'],
      ['value'=>'dz', 'label' => 'Dzongkha'],
      ['value'=>'en', 'label' => 'English'],
      ['value'=>'eo', 'label' => 'Esperanto'],
      ['value'=>'et', 'label' => 'Estonian'],
      ['value'=>'ee', 'label' => 'Ewe'],
      ['value'=>'fo', 'label' => 'Faroese'],
      ['value'=>'fj', 'label' => 'Fijian'],
      ['value'=>'fi', 'label' => 'Finnish'],
      ['value'=>'fr', 'label' => 'French'],
      ['value'=>'ff', 'label' => 'Fulah'],
      ['value'=>'gd', 'label' => 'Gaelic, Scottish Gaelic'],
      ['value'=>'gl', 'label' => 'Galician'],
      ['value'=>'lg', 'label' => 'Ganda'],
      ['value'=>'ka', 'label' => 'Georgian'],
      ['value'=>'de', 'label' => 'German'],
      ['value'=>'ki', 'label' => 'Gikuyu, Kikuyu'],
      ['value'=>'el', 'label' => 'Greek (Modern)'],
      ['value'=>'kl', 'label' => 'Greenlandic, Kalaallisut'],
      ['value'=>'gn', 'label' => 'Guarani'],
      ['value'=>'gu', 'label' => 'Gujarati'],
      ['value'=>'ht', 'label' => 'Haitian, Haitian Creole'],
      ['value'=>'ha', 'label' => 'Hausa'],
      ['value'=>'he', 'label' => 'Hebrew'],
      ['value'=>'hz', 'label' => 'Herero'],
      ['value'=>'hi', 'label' => 'Hindi'],
      ['value'=>'ho', 'label' => 'Hiri Motu'],
      ['value'=>'hu', 'label' => 'Hungarian'],
      ['value'=>'is', 'label' => 'Icelandic'],
      ['value'=>'io', 'label' => 'Ido'],
      ['value'=>'ig', 'label' => 'Igbo'],
      ['value'=>'id', 'label' => 'Indonesian'],
      ['value'=>'ia', 'label' => 'Interlingua (International Auxiliary Language Association)'],
      ['value'=>'ie', 'label' => 'Interlingue'],
      ['value'=>'iu', 'label' => 'Inuktitut'],
      ['value'=>'ik', 'label' => 'Inupiaq'],
      ['value'=>'ga', 'label' => 'Irish'],
      ['value'=>'it', 'label' => 'Italian'],
      ['value'=>'ja', 'label' => 'Japanese'],
      ['value'=>'jv', 'label' => 'Javanese'],
      ['value'=>'kn', 'label' => 'Kannada'],
      ['value'=>'kr', 'label' => 'Kanuri'],
      ['value'=>'ks', 'label' => 'Kashmiri'],
      ['value'=>'kk', 'label' => 'Kazakh'],
      ['value'=>'rw', 'label' => 'Kinyarwanda'],
      ['value'=>'kv', 'label' => 'Komi'],
      ['value'=>'kg', 'label' => 'Kongo'],
      ['value'=>'ko', 'label' => 'Korean'],
      ['value'=>'kj', 'label' => 'Kwanyama, Kuanyama'],
      ['value'=>'ku', 'label' => 'Kurdish'],
      ['value'=>'ky', 'label' => 'Kyrgyz'],
      ['value'=>'lo', 'label' => 'Lao'],
      ['value'=>'la', 'label' => 'Latin'],
      ['value'=>'lv', 'label' => 'Latvian'],
      ['value'=>'lb', 'label' => 'Letzeburgesch, Luxembourgish'],
      ['value'=>'li', 'label' => 'Limburgish, Limburgan, Limburger'],
      ['value'=>'ln', 'label' => 'Lingala'],
      ['value'=>'lt', 'label' => 'Lithuanian'],
      ['value'=>'lu', 'label' => 'Luba-Katanga'],
      ['value'=>'mk', 'label' => 'Macedonian'],
      ['value'=>'mg', 'label' => 'Malagasy'],
      ['value'=>'ms', 'label' => 'Malay'],
      ['value'=>'ml', 'label' => 'Malayalam'],
      ['value'=>'mt', 'label' => 'Maltese'],
      ['value'=>'gv', 'label' => 'Manx'],
      ['value'=>'mi', 'label' => 'Maori'],
      ['value'=>'mr', 'label' => 'Marathi'],
      ['value'=>'mh', 'label' => 'Marshallese'],
      ['value'=>'ro', 'label' => 'Moldovan, Moldavian, Romanian'],
      ['value'=>'mn', 'label' => 'Mongolian'],
      ['value'=>'na', 'label' => 'Nauru'],
      ['value'=>'nv', 'label' => 'Navajo, Navaho'],
      ['value'=>'nd', 'label' => 'Northern Ndebele'],
      ['value'=>'ng', 'label' => 'Ndonga'],
      ['value'=>'ne', 'label' => 'Nepali'],
      ['value'=>'se', 'label' => 'Northern Sami'],
      ['value'=>'no', 'label' => 'Norwegian'],
      ['value'=>'nb', 'label' => 'Norwegian Bokmål'],
      ['value'=>'nn', 'label' => 'Norwegian Nynorsk'],
      ['value'=>'ii', 'label' => 'Nuosu, Sichuan Yi'],
      ['value'=>'oc', 'label' => 'Occitan (post 1500)'],
      ['value'=>'oj', 'label' => 'Ojibwa'],
      ['value'=>'or', 'label' => 'Oriya'],
      ['value'=>'om', 'label' => 'Oromo'],
      ['value'=>'os', 'label' => 'Ossetian, Ossetic'],
      ['value'=>'pi', 'label' => 'Pali'],
      ['value'=>'pa', 'label' => 'Panjabi, Punjabi'],
      ['value'=>'ps', 'label' => 'Pashto, Pushto'],
      ['value'=>'fa', 'label' => 'Persian'],
      ['value'=>'pl', 'label' => 'Polish'],
      ['value'=>'pt', 'label' => 'Portuguese'],
      ['value'=>'qu', 'label' => 'Quechua'],
      ['value'=>'rm', 'label' => 'Romansh'],
      ['value'=>'rn', 'label' => 'Rundi'],
      ['value'=>'ru', 'label' => 'Russian'],
      ['value'=>'sm', 'label' => 'Samoan'],
      ['value'=>'sg', 'label' => 'Sango'],
      ['value'=>'sa', 'label' => 'Sanskrit'],
      ['value'=>'sc', 'label' => 'Sardinian'],
      ['value'=>'sr', 'label' => 'Serbian'],
      ['value'=>'sn', 'label' => 'Shona'],
      ['value'=>'sd', 'label' => 'Sindhi'],
      ['value'=>'si', 'label' => 'Sinhala, Sinhalese'],
      ['value'=>'sk', 'label' => 'Slovak'],
      ['value'=>'sl', 'label' => 'Slovenian'],
      ['value'=>'so', 'label' => 'Somali'],
      ['value'=>'st', 'label' => 'Sotho, Southern'],
      ['value'=>'nr', 'label' => 'South Ndebele'],
      ['value'=>'es', 'label' => 'Spanish'],
      ['value'=>'su', 'label' => 'Sundanese'],
      ['value'=>'sw', 'label' => 'Swahili'],
      ['value'=>'ss', 'label' => 'Swati'],
      ['value'=>'sv', 'label' => 'Swedish'],
      ['value'=>'tl', 'label' => 'Tagalog'],
      ['value'=>'ty', 'label' => 'Tahitian'],
      ['value'=>'tg', 'label' => 'Tajik'],
      ['value'=>'ta', 'label' => 'Tamil'],
      ['value'=>'tt', 'label' => 'Tatar'],
      ['value'=>'te', 'label' => 'Telugu'],
      ['value'=>'th', 'label' => 'Thai'],
      ['value'=>'bo', 'label' => 'Tibetan'],
      ['value'=>'ti', 'label' => 'Tigrinya'],
      ['value'=>'to', 'label' => 'Tonga (Tonga Islands)'],
      ['value'=>'ts', 'label' => 'Tsonga'],
      ['value'=>'tn', 'label' => 'Tswana'],
      ['value'=>'tr', 'label' => 'Turkish'],
      ['value'=>'tk', 'label' => 'Turkmen'],
      ['value'=>'tw', 'label' => 'Twi'],
      ['value'=>'ug', 'label' => 'Uighur, Uyghur'],
      ['value'=>'uk', 'label' => 'Ukrainian'],
      ['value'=>'ur', 'label' => 'Urdu'],
      ['value'=>'uz', 'label' => 'Uzbek'],
      ['value'=>'ve', 'label' => 'Venda'],
      ['value'=>'vi', 'label' => 'Vietnamese'],
      ['value'=>'vo', 'label' => 'Volap_k'],
      ['value'=>'wa', 'label' => 'Walloon'],
      ['value'=>'cy', 'label' => 'Welsh'],
      ['value'=>'fy', 'label' => 'Western Frisian'],
      ['value'=>'wo', 'label' => 'Wolof'],
      ['value'=>'xh', 'label' => 'Xhosa'],
      ['value'=>'yi', 'label' => 'Yiddish'],
      ['value'=>'yo', 'label' => 'Yoruba'],
      ['value'=>'za', 'label' => 'Zhuang, Chuang'],
      ['value'=>'zu', 'label' => 'Zulu']
    ];

    return $languages;
  }

  public function checkLanguageByLabel($language_label){
    if($language_label){
      $language_list = $this->getLanguages();
      $selected_value = "";
      foreach ($language_list as $key => $item) {
        if($item['label'] == $language_label){
          $selected_value = $item['value'];
          break;
        }
      }
      return $selected_value;
    }else{
      return '';
    }
  }

  public function checkCountryByLabel($country_label){
    if($country_label){
      $country_list = $this->getCountries();
      $selected_value = "";
      foreach ($country_list as $key => $item) {
        if($item['label'] == $country_label){
          $selected_value = $item['value'];
          break;
        }
      }
      return $selected_value;
    }else{
      return '';
    }
  }
}
