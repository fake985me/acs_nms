@extends('layouts.app')

@section('title', 'Network Topology')

@section('content')
<div class="content-wrapper">
    <div class="flex items-center justify-between mb-6">
        <h1 style="font-size: 1.875rem; font-weight: 700;">Network Topology</h1>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="filterTopology('all')" class="btn btn-sm btn-secondary" id="btn-all">All</button>
            <button onclick="filterTopology('ftth')" class="btn btn-sm btn-secondary" id="btn-ftth">FTTH</button>
            <button onclick="filterTopology('fttb')" class="btn btn-sm btn-secondary" id="btn-fttb">FTTB</button>
            <button onclick="exportTopology()" class="btn btn-sm btn-primary">Export PNG</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 0;">
            <div id="topology-network" style="height: 600px; background: var(--dark-bg);"></div>
        </div>
    </div>

    <div class="grid-2 mb-4" style="margin-top: 1rem;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Legend</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 16px; height: 16px; background: #3b82f6; border-radius: 50%;"></div>
                        <span>OLT (Core Equipment)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 16px; height: 16px; background: #8b5cf6; border-radius: 50%;"></div>
                        <span>PON Port</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 16px; height: 16px; background: #10b981; border-radius: 50%;"></div>
                        <span>ONT (Online)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 16px; height: 16px; background: #ef4444; border-radius: 50%;"></div>
                        <span>ONT (Offline)</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Network Statistics</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div class="text-muted" style="font-size: 0.875rem;">Total OLTs</div>
                        <div style="font-size: 1.5rem; font-weight: 600;" id="stat-olts">-</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.875rem;">Total ONTs</div>
                        <div style="font-size: 1.5rem; font-weight: 600;" id="stat-onts">-</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.875rem;">Online ONTs</div>
                        <div style="font-size: 1.5rem; font-weight: 600; color: var(--success);" id="stat-online">-</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.875rem;">Offline ONTs</div>
                        <div style="font-size: 1.5rem; font-weight: 600; color: var(--danger);" id="stat-offline">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load vis.js -->
<link href="https://unpkg.com/vis-network@9.1.2/styles/vis-network.min.css" rel="stylesheet">
<script src="https://unpkg.com/vis-network@9.1.2/dist/vis-network.min.js"></script>

<script>
let network;
let currentFilter = 'all';

document.addEventListener('DOMContentLoaded', function() {
    initNetwork();
});

function initNetwork() {
    const container = document.getElementById('topology-network');
    
    const options = {
        layout: {
            hierarchical: {
                enabled: true,
                direction: 'UD',
                sortMethod: 'directed',
                levelSeparation: 150,
                nodeSpacing: 100,
            }
        },
        nodes: {
            shape: 'dot',
            size: 20,
            font: {
                size: 12,
                color: '#e2e8f0',
            },
            borderWidth: 2,
        },
        edges: {
            width: 2,
            color: { color: '#475569', highlight: '#3b82f6' },
            smooth: {
                type: 'cubicBezier',
                forceDirection: 'vertical',
            }
        },
        groups: {
            olt: {
                color: { background: '#3b82f6', border: '#1e40af' },
                size: 30,
                font: { size: 14, bold: true },
            },
            pon: {
                color: { background: '#8b5cf6', border: '#6d28d9' },
                size: 20,
            },
            'ont-online': {
                color: { background: '#10b981', border: '#059669' },
                size: 15,
            },
            'ont-offline': {
                color: { background: '#ef4444', border: '#dc2626' },
                size: 15,
            },
        },
        physics: {
            enabled: false,
        },
        interaction: {
            hover: true,
            tooltipDelay: 100,
        }
    };

    network = new vis.Network(container, {}, options);
    
    loadTopologyData();
}

function loadTopologyData() {
    fetch('/network/topology-data?filter=' + currentFilter)
        .then(response => response.json())
        .then(data => {
            const nodes = new vis.DataSet(data.nodes);
            const edges = new vis.DataSet(data.edges);
            
            network.setData({ nodes, edges });
            
            // Update statistics
            const oltCount = data.nodes.filter(n => n.group === 'olt').length;
            const ontOnline = data.nodes.filter(n => n.group === 'ont-online').length;
            const ontOffline = data.nodes.filter(n => n.group === 'ont-offline').length;
            
            document.getElementById('stat-olts').textContent = oltCount;
            document.getElementById('stat-onts').textContent = ontOnline + ontOffline;
            document.getElementById('stat-online').textContent = ontOnline;
            document.getElementById('stat-offline').textContent = ontOffline;
        })
        .catch(error => console.error('Failed to load topology:', error));
}

function filterTopology(filter) {
    currentFilter = filter;
    
    // Update button states
    document.querySelectorAll('[id^="btn-"]').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
    });
    document.getElementById('btn-' + filter).classList.remove('btn-secondary');
    document.getElementById('btn-' + filter).classList.add('btn-primary');
    
    loadTopologyData();
}

function exportTopology() {
    const canvas = network.canvas.frame.canvas;
    const link = document.createElement('a');
    link.download = 'network-topology.png';
    link.href = canvas.toDataURL();
    link.click();
}
</script>
@endsection
