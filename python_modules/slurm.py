#!/usr/bin/python
# /* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

import glob, os.path
import logging, time

#logging.basicConfig(level=logging.DEBUG)
logging.basicConfig()
logging.debug('Starting')

stats = {}
last_update = 0

MAX_UPDATE = 5

PARAMS = {
    'cg_memory' : '/cgroup/memory/slurm',
    'cg_cpuset' : '/cgroup/cpuset/slurm',
    'debug'     : False,
}

def update_stats():
    logging.debug('update_stats')
    global last_update, stats

    cur_time = time.time()
    if cur_time - last_update < MAX_UPDATE:
        logging.debug('skipping update')
        return True

    stats = get_slurm_stats()

    logging.debug(stats)
    last_update = cur_time

##############################################################################
def get_line(file):
    # Return the first line from a given filename
    with open(file) as fp:
        return fp.readline().rstrip()

def cpus_to_count(c):
    # Given a cpuset.cpus list format, return count
    # Ex. 1,3-5 => 4
    parts = c.split(',')

    count = 0

    for p in parts:
        try:
            start, end = p.split('-')
            count += (int(end) - int(start) + 1)
        except ValueError:
            count += 1

    return count

def get_slurm_stats():
    out = {}
    alloc_mem = 0
    used_mem  = 0
    alloc_cpu = 0

    for fn in glob.glob(os.path.join(PARAMS['cg_memory'], 'uid_*/job_*')):
        used_mem  += int(get_line(os.path.join(fn, 'memory.usage_in_bytes')))
        alloc_mem += int(get_line(os.path.join(fn, 'memory.limit_in_bytes')))

    for fn in glob.glob(os.path.join(PARAMS['cg_cpuset'], 'uid_*/job_*')):
        cpus = get_line(os.path.join(fn, 'cpuset.cpus'))
        alloc_cpu += cpus_to_count(cpus)

    out['slurm_alloc_cpu'] = alloc_cpu
    out['slurm_alloc_mem'] = alloc_mem
    out['slurm_used_mem']  = used_mem

    return out

##############################################################################

def create_desc(skel, prop):
    d = skel.copy()
    for k, v in prop.iteritems():
        d[k] = v
    return d

def get_value(key):
    update_stats()

    if key in stats:
        return stats[key]
    else:
        return None

def metric_init(params):
    global PARAMS

    PARAMS.update(params)

    if PARAMS['debug']:
        logging.getLogger().setLevel(logging.DEBUG)

    logging.debug('metric_init: ' + str(params))
    update_stats()

    Desc_Skel = {
        'name'        : 'XXX',
        'call_back'   : get_value,
        'time_max'    : 60,
        'value_type'  : 'uint',
        'format'      : '%u',
        'units'       : 'ops',
        'slope'       : 'positive', # zero|positive|negative|both
        'description' : 'XXX',
        'groups'      : 'slurm',
    }

    descriptors = []

    descriptors.append(create_desc(Desc_Skel, {
        'name'        : 'slurm_alloc_cpu',
        'description' : 'Allocated CPUs',
        'units'       : 'CPUs',
    }))
    descriptors.append(create_desc(Desc_Skel, {
        'name'        : 'slurm_alloc_mem',
        'description' : 'Allocated memory (reserved for jobs)',
        'units'       : 'bytes',
    }))
    descriptors.append(create_desc(Desc_Skel, {
        'name'        : 'slurm_used_mem',
        'description' : 'Used memory',
        'units'       : 'bytes',
    }))

    return descriptors

def metric_cleanup():
    '''Clean up the metric module.'''
    pass

# For testing
if __name__ == '__main__':
    params = {
        'debug' : True,
    }
    descriptors = metric_init(params)
    for d in descriptors:
        v = d['call_back'](d['name'])
        print 'value for %s is %s' % (d['name'],  v)

