<?php
/**
* Driving App
*   Copyright © 2010, 2012 Theodore R. Smith <theodore@phpexperts.pro>
* 
* The following code is licensed under a modified BSD License.
* All of the terms and conditions of the BSD License apply with one
* exception:
*
* 1. Every one who has not been a registered student of the "PHPExperts
*    From Beginner To Pro" course (http://www.phpexperts.pro/) is forbidden
*    from modifing this code or using in an another project, either as a
*    deritvative work or stand-alone.
*
* BSD License: http://www.opensource.org/licenses/bsd-license.php
**/

abstract class Car extends CarPartSubject implements Automobile
{
    const STATE_POWERED_OFF = 0;
    const STATE_POWERED_ON = 1;

    const NOTICE_STATE_CHANGED = 'The car\'s state has changed';

    // Objects for the Composite Pattern.
    /* @var AutomobileStats */
    protected static $carStats;

    /** @var Engine */
    protected $engine;

    /** @var CarDriveTrain */
    protected $drivetrain;

    /** @var GasTank */
    protected $gasTank;

    /** @var GearShaft */
    protected $gearShaft;

    // Class properties.
    protected $state;
    protected $currentGear;

	// TODO: This almost certainly needs to be moved to the main library autoload class.
    public static function autoloader($className)
    {
        if (($pos = strpos($className, "Car")) !== false)
        {
            $filename = CARS_LIB_PATH . "/cars/" . substr($className, 0, $pos) . ".car.php";

            require $filename;            
        }
    }

	public function __construct(Engine $engine, GasTank $gasTank, GearShaft $gearShaft, DriveTrain $driveTrain)
	{
		$this->engine     = $engine;
		$this->gasTank    = $gasTank;
		$this->gearShaft  = $gearShaft;
		$this->drivetrain = $driveTrain;
	}

    public function turnOn()
    {
        $this->state = self::STATE_POWERED_ON;
        $this->ro_official_notice = array('notice' => self::NOTICE_STATE_CHANGED,
                                       'value' => $this->state);
        // Functional equivalent of running $this->notify();
        attemptAction(get_class($this), array('turn on the car', 'turned on the car'), array($this, 'notify'), array($this->currentGear - 1));
    }

    public function turnOff()
    {
        $this->state = self::STATE_POWERED_OFF;
        $this->ro_official_notice = array('notice' => self::NOTICE_STATE_CHANGED,
                                       'value' => $this->state);
        // Functional equivalent of running $this->notify();
        attemptAction(get_class($this), 
                      array('turn off the car', 'turned off the car'), 
                      array($this, 'notify'), 
                      array($this->currentGear - 1));
    }

    // Right now our car will only be able to drive in a straight line
    // either forward or in reverse.
    public function accelerate($footPressure, $secondsToAccelerate)
    {
        // Sanity checks.
        // Functional equivalent of running $this->gearShaft->ensureDrivableState();
        attemptAction(get_class($this), 
                      null, 
                      array($this->gearShaft, 'ensureDrivableState'));
        
        
        // Use a loop; one second == one iteration.
        for ($a = 0; $a < $secondsToAccelerate; ++$a)
        {
            $this->engine->revUp($footPressure);
        }
    }

    public function drive($minutesToDrive, $steeringWheelAngle)
    {
        // Sanity checks.
        // Functional equivalent of running $this->gearShaft->ensureDrivableState();
        attemptAction(get_class($this), 
                      null, 
                      array($this->gearShaft, 'ensureDrivableState'));

        $this->drivetrain->turn($steeringWheelAngle);

        // Use a loop; one minute == one iteration.
        for ($a = 0; $a < $minutesToDrive; ++$a)
        {
            $this->engine->rev();
        }
    }

    public function brake($footPressure)
    {
        $this->engine->revDown($footPressure);
    }

    public function refuel($amount = GasTank::REFUEL_UNTIL_FULL)
    {
        try
        {
            // Functional equivalent of running $this->gasTank->refuel($amount);
            attemptAction(get_class($this), 
                          array('refuel by ' . $amount . ' gallons', 'refueled by ' . $amount . ' gallons'), 
                          array($this->gasTank, 'refuel'), 
                          array($amount));
        }
        catch(GasTankException $e)
        {
            if ($e->getMessage() == GasTankException::NOTICE_TOO_MUCH_GAS)
            {
                printf("Inform the clerk that %.2f gallons needs to be refunded.\n", $e->remainingGas);
            }
            else
            {
                // Something else happened.
                throw $e;
            }
        }
    }

    public function getMileage()
    {
        return (float)$this->drivetrain->getDistanceTravelled();
    }

    public function calculateFuelEfficiency()
    {
        $fuelUsed = $this->gasTank->calculateFuelUsedPerTank();

        // Sanity checks.
        // Prevent divide by zero.
        if ($fuelUsed == 0)
        {
            return 'NaN';
        }

        $distance = $this->getMileage();

        return $distance / $fuelUsed;
    }

    public function getFuelRemaining()
    {
        return $this->gasTank->getFuelRemaining();
    }

    public function downShift()
    {
        // downShift irrationally increases the gear value.
        // Functional equivalent of running $this->gearShaft->changeGear($this->currentGear + 1);
        attemptAction(get_class($this), 'downshift', array($this->gearShaft, 'changeGear'), array($this->currentGear + 1));
        ++$this->currentGear;

        return $this->currentGear;
    }

    public function upShift()
    {
        // Upshift irrationally decreases the gear value.        
        // Functional equivalent of running $this->gearShaft->changeGear($this->currentGear - 1);
        attemptAction(get_class($this), 'upshift', array($this->gearShaft, 'changeGear'), array($this->currentGear - 1));
        --$this->currentGear;

        return $this->currentGear;
    }

    public function getSpeed()
    {
        return $this->drivetrain->getSpeed();
    }

    public static function formatStat($statistic)
    {
        return sprintf('%.2f', round($statistic, 2));
    }
}

