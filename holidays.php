<?php

function rsvpmakerDefaultHolidays() {
    $holiday = get_option('rsvpmaker_holidays');
    if(!$holiday) {
        $holiday = array();
        $holiday[] = array('schedule'=>'January 1','name'=>"New Year's Day",'default' => false, 'overflow' => 'weekend');
        $holiday[] = array('schedule'=>'Third Monday of January','name'=>'Martin Luther King Day (US)','default' => false, 'overflow' => '');
        $holiday[] = array('schedule'=>'Last Monday of May','name'=>'Memorial Day (US)','default' => false, 'overflow' => '');
        $holiday[]  = array('schedule'=>'July 4','name'=>'Independence Day (US)','default' => false, 'overflow' => 'weekend');
        $holiday[] = array('schedule'=>'First Monday of September','name'=>'Labor Day (US)','default' => false, 'overflow' => '');
        $holiday[] = array('schedule'=>'November 11','name'=>'Veterans Day (US)','default' => false, 'overflow' => 'weekend');
        $holiday[] = array('schedule'=>'Fourth Thursday of November','name'=>'Thanksgiving (US)','default' => false, 'overflow' => 'dayafter');
        $holiday[] = array('schedule'=>'December 24','name'=>'Christmas Eve','default' => false, 'overflow' => '');
        $holiday[] = array('schedule'=>'December 25','name'=>'Christmas','default' => false, 'overflow' => 'dayafter');
        $holiday[] = array('schedule'=>'December 31','name'=>'New Year\'s Eve','default' => false, 'overflow' => '');
    }
    return $holiday;
}

function commonHolidays($year = '') {
    if(!$year)
        $year = date('Y');
    $schedule = rsvpmakerDefaultHolidays();
    foreach($schedule as $s) {
        if(strpos($s['schedule'],' to ')) {
            $parts = explode(' to ',$s['schedule']);
            $t = strtotime($parts[0].' '.$year);
            $end = strtotime($parts[1].' '.$year);
            while($t <= $end) {
                $holiday[date('Y-m-d',$t)] = $s;
                $t += DAY_IN_SECONDS;
            }
            continue;
        }
        $stext = $s['schedule'] . ' '. $year;
        $t = strtotime( $stext );
        if($t < time())
            continue;
        $dow = date('w',$t);
        if(($s['overflow'] == 'weekend') && ($dow == 6))
            $holiday[date('Y-m-d',$t - DAY_IN_SECONDS)] = array('name' => 'Friday before '.$s['name'], 'default' => $s['default']);
        $holiday[date('Y-m-d',$t)] = $s;
        if(($s['overflow'] == 'weekend') && ($dow == 0))
            $holiday[date('Y-m-d',$t + DAY_IN_SECONDS)] = array('name' => 'Monday after '.$s['name'],'default' => $s['default']);
        if($s['overflow'] == 'dayafter')
            $holiday[date('Y-m-d',$t + DAY_IN_SECONDS)] = array('name' => 'Day after '.$s['name'],'default' => $s['default']);
    }

    if(date('n') < 6)
        return $holiday;

    $year++;
    foreach($schedule as $s) {
        if(strpos($s['schedule'],' to ')) {
            $parts = explode(' to ',$s['schedule']);
            $t = strtotime($parts[0].' '.$year);
            $end = strtotime($parts[1].' '.$year);
            while($t <= $end) {
                $holiday[date('Y-m-d',$t)] = $s;
                $t += DAY_IN_SECONDS;
            }
            continue;
        }
        $stext = $s['schedule'] . ' '. $year;
        $t = strtotime( $stext );
        $dow = date('w',$t);
        if(($s['overflow'] == 'weekend') && ($dow == 6))
            $holiday[date('Y-m-d',$t - DAY_IN_SECONDS)] = array('name' => 'Friday before '.$s['name'],'default' => $s['default']);
        $holiday[date('Y-m-d',$t)] = $s;
        if(($s['overflow'] == 'weekend') && ($dow == 0))
            $holiday[date('Y-m-d',$t + DAY_IN_SECONDS)] = array('name' => 'Monday after '.$s['name'],'default' => $s['default']);
        if($s['overflow'] == 'dayafter')
            $holiday[date('Y-m-d',$t + DAY_IN_SECONDS)] = array('name' => 'Day after '.$s['name'],'default' => $s['default']);
    }

    return $holiday;
}

function rsvpmaker_holiday_check($thistime,$holidays) {
    $date = rsvpmaker_date('Y-m-d',$thistime);
    if(empty($holidays[$date]))
        return false;
    $s = $holidays[$date];
    $s['hwarn'] = ($s['default']) ? '<span style="color:red" class="hwarn">Skip '.$date : '<span class="hwarn"><em>Note for '.$date.'</em>';
    $s['hwarn'] .= ' '.$s['name'].' (see <a href="'.admin_url('edit.php?post_type=rsvpmaker&page=rsvpmaker_template_list#holidays').'">holidays</a>)</span>';
    return $s;
}