<?php

/* Pass in by reference! */
function graph_slurm_mem_report ( &$rrdtool_graph ) {

    global $conf,
           $context,
           $range,
           $rrd_dir,
           $size;

    if ($conf['strip_domainname']) {
       $hostname = strip_domainname($GLOBALS['hostname']);
    } else {
       $hostname = $GLOBALS['hostname'];
    }

    $title = 'SLURM memory';
    $rrdtool_graph['title'] = $title;
    $rrdtool_graph['lower-limit'] = '0';
    $rrdtool_graph['vertical-label'] = 'Bytes';
    $rrdtool_graph['extras'] = '--base 1024';
    $rrdtool_graph['height'] += ($size == 'medium') ? 28 : 0;

    # Scale right axis for 4G/core
    $cpu_scale = 1/(4*pow(1024,3));
    $rrdtool_graph['extras'] .= " --right-axis $cpu_scale:0 --right-axis-label CPUs ";

    if ( $conf['graphreport_stats'] ) {
        $rrdtool_graph['height'] += ($size == 'medium') ? 4 : 0;
        $rmspace = '\\g';
    } else {
        $rmspace = '';
    }
    $rrdtool_graph['extras'] .= ($conf['graphreport_stats'] == true) ? ' --font LEGEND:7' : '';

    if ($size == 'small') {
       $eol1 = '\\l';
       $space1 = ' ';
       $space2 = '         ';
    } else if ($size == 'medium' || $size = 'default') {
       $eol1 = '';
       $space1 = ' ';
       $space2 = '';
    } else if ($size == 'large') {
       $eol1 = '';
       $space1 = '                 ';
       $space2 = '                 ';
    }

    $series = "DEF:'mem_total'='${rrd_dir}/mem_total.rrd':'sum':AVERAGE "
        ."CDEF:'bmem_total'=mem_total,1024,* "
        ."DEF:'cpu_alloc'='${rrd_dir}/slurm_alloc_cpu.rrd':'sum':AVERAGE "
        ."DEF:'bmem_alloc'='${rrd_dir}/slurm_alloc_mem.rrd':'sum':AVERAGE "
        ."DEF:'bmem_used'='${rrd_dir}/slurm_used_mem.rrd':'sum':AVERAGE ";

    $series .= "AREA:'bmem_alloc'#${conf['mem_cached_color']}:'Alloc${rmspace}' ";

    if ( $conf['graphreport_stats'] ) {
        $series .= "CDEF:alloc_pos=bmem_alloc,0,INF,LIMIT "
                . "VDEF:alloc_last=alloc_pos,LAST "
                . "VDEF:alloc_min=alloc_pos,MINIMUM " 
                . "VDEF:alloc_avg=alloc_pos,AVERAGE " 
                . "VDEF:alloc_max=alloc_pos,MAXIMUM " 
                . "GPRINT:'alloc_last':' ${space1}Now\:%6.1lf%s' "
                . "GPRINT:'alloc_min':'${space1}Min\:%6.1lf%s${eol1}' "
                . "GPRINT:'alloc_avg':'${space2}Avg\:%6.1lf%s' "
                . "GPRINT:'alloc_max':'${space1}Max\:%6.1lf%s\\l' ";
    }

    $series .= "AREA:'bmem_used'#${conf['mem_used_color']}:'Use${rmspace}' ";

    if ( $conf['graphreport_stats'] ) {
        $series .= "CDEF:used_pos=bmem_used,0,INF,LIMIT " 
                . "VDEF:used_last=used_pos,LAST "
                . "VDEF:used_min=used_pos,MINIMUM " 
                . "VDEF:used_avg=used_pos,AVERAGE " 
                . "VDEF:used_max=used_pos,MAXIMUM " 
                . "GPRINT:'used_last':'   ${space1}Now\:%6.1lf%s' "
                . "GPRINT:'used_min':'${space1}Min\:%6.1lf%s${eol1}' "
                . "GPRINT:'used_avg':'${space2}Avg\:%6.1lf%s' "
                . "GPRINT:'used_max':'${space1}Max\:%6.1lf%s\\l' ";
    }

    $series .= "LINE2:'bmem_total'#${conf['cpu_num_color']}:'Total${rmspace}' ";

    if ( $conf['graphreport_stats'] ) {
        $series .= "CDEF:total_pos=bmem_total,0,INF,LIMIT "
                . "VDEF:total_last=total_pos,LAST "
                . "VDEF:total_min=total_pos,MINIMUM " 
                . "VDEF:total_avg=total_pos,AVERAGE " 
                . "VDEF:total_max=total_pos,MAXIMUM " 
                . "GPRINT:'total_last':' ${space1}Now\:%6.1lf%s' "
                . "GPRINT:'total_min':'${space1}Min\:%6.1lf%s${eol1}' "
                . "GPRINT:'total_avg':'${space2}Avg\:%6.1lf%s' "
                . "GPRINT:'total_max':'${space1}Max\:%6.1lf%s\\l' ";
    }

    $series .= "CDEF:cpu_alloc_scale=cpu_alloc,$cpu_scale,/ ";
    $series .= "LINE2:'cpu_alloc_scale'#${conf['num_nodes_color']}:'CPUs${rmspace}' ";

    if ( $conf['graphreport_stats'] ) {
        $series .= "CDEF:cpu_pos=cpu_alloc,0,INF,LIMIT "
                . "VDEF:cpu_last=cpu_pos,LAST "
                . "VDEF:cpu_min=cpu_pos,MINIMUM " 
                . "VDEF:cpu_avg=cpu_pos,AVERAGE " 
                . "VDEF:cpu_max=cpu_pos,MAXIMUM " 
                . "GPRINT:'cpu_last':'  ${space1}Now\:%4.lf%s  ' "
                . "GPRINT:'cpu_min':'${space1}Min\:%4.lf%s${eol1}  ' "
                . "GPRINT:'cpu_avg':'${space2}Avg\:%4.lf%s  ' "
                . "GPRINT:'cpu_max':'${space1}Max\:%4.lf%s\\l' ";
    }

    // If metrics like slurm_alloc_mem are not present we are likely not collecting them on this
    // host therefore we should not attempt to build anything and will likely end up with a broken
    // image. To avoid that we'll make an empty image
    if ( !file_exists("$rrd_dir/slurm_alloc_mem.rrd") ) 
      $rrdtool_graph[ 'series' ] = 'HRULE:1#FFCC33:"No matching metrics detected"';   
    else
      $rrdtool_graph[ 'series' ] = $series;

    return $rrdtool_graph;
}

?>
