<?php
namespace GLOTR;
/**
 * Template helpers
 */
class Helpers
{
	/**
	 * Used by Nette to obtain helper callback
	 * @param string $helper
	 * @return callable
	 */
	public static function loader($helper)
    {
        if (method_exists(__CLASS__, $helper)) {
            return callback(__CLASS__, $helper);
        }
    }
	/**
	 * Format seconds to time string
	 * @param int $seconds
	 * @return string
	 */
    public static function formatSeconds($seconds)
    {
		$days = $hours = $weeks = $months = $years = NULL;
		$ret = "";
		$minutes = floor($seconds/60);
		$seconds -= $minutes*60;
		if($minutes >= 60)
		{
			$hours = floor($minutes/60);
			$minutes -= $hours*60;
			if($hours >= 24)
			{
				$days = floor($hours/24);
				$hours -= $days*24;
				if($days >= 365)
				{
					$years = floor($days/365);
					$days -= $years*365;
				}
				if($days >= 30)
				{
					$months = floor($days/30);
					$days -= $months*30;
				}
				if($days >= 7)
				{
					$weeks = floor($days/7);
					$days -= $weeks*7;
				}
			}
		}
		if($seconds < 10)
			$seconds = "0".$seconds;
		if($minutes < 10)
			$minutes = "0".$minutes;
		if($hours < 10)
			$hours = "0".$hours;
		$ret = $minutes.":".$seconds;
		if($hours !== NULL)
		{
			$ret = $hours . ":".$ret;
			if($days !== NULL)
			{
				$ret = $days . "d ".$ret;
				if($weeks !== NULL)
				{
					$ret = $weeks . "w " . $ret;
					if($months !== NULL)
					{
						$ret = $months . "m " . $ret;
						if($years !== NULL)
						{
							$ret = $years . "y " . $ret;
						}
					}
				}
			}

		}
		return $ret;
    }
	/**
	 * Format database key to label
	 * @param string $key
	 * @return string
	 */
	public static function key2Text($key)
	{
		$key = str_replace("moon_", "", $key);
		$key = str_replace("_", " ", $key);
		return $key;
	}
}