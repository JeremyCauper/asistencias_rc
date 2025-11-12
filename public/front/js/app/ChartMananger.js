class ChartMananger {
    estadoAnteriorMap = null;

    constructor(params = {}) {
        this.id = params.id ?? null;
        this.data = params.data ?? [];
        this.config = params.config ?? {};
        this.config.order = params.config?.order ?? 'desc';

        if (!this.id) return console.error('ChartManager: ID o datos no válidos.');

        const container = document.getElementById(this.id);
        if (!container) return console.error(`ChartManager: No se encontró el contenedor con ID ${this.id}`);
        let altura = this.config?.altura || 40;
        container.style = `position: relative; height: ${altura}vh; overflow: hidden; width: 100%;`;

        this.chart = echarts.init(container, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });

        this.chart.setOption(this._buildOptionEstado());
    }

    _buildOptionEstado() {
        let option = null;
        switch (this.config.tipo) {
            case "estado":
                var total = this.data.total;
                var value = this.data.value;
                this.data.porcentaje = (value / total) * 100;

                option = {
                    series: [
                        {
                            type: 'gauge',
                            center: ['50%', '85%'],
                            startAngle: 180,
                            endAngle: 0,
                            min: 0,
                            max: 100,
                            splitNumber: 1,
                            itemStyle: {
                                color: this.config.bg
                            },
                            pointer: {
                                icon: 'path://M2090.36389,615.30999 L2090.36389,615.30999 C2091.48372,615.30999 2092.40383,616.194028 2092.44859,617.312956 L2096.90698,728.755929 C2097.05155,732.369577 2094.2393,735.416212 2090.62566,735.56078 C2090.53845,735.564269 2090.45117,735.566014 2090.36389,735.566014 L2090.36389,735.566014 C2086.74736,735.566014 2083.81557,732.63423 2083.81557,729.017692 C2083.81557,728.930412 2083.81732,728.84314 2083.82081,728.755929 L2088.2792,617.312956 C2088.32396,616.194028 2089.24407,615.30999 2090.36389,615.30999 Z',
                                length: '60%',
                                width: 3,
                                offsetCenter: [0, '5%']
                            },
                            progress: {
                                show: true,
                                roundCap: true,
                                width: 5
                            },
                            axisLine: {
                                roundCap: true,
                                lineStyle: {
                                    width: 5
                                }
                            },
                            axisTick: {
                                show: false
                            },
                            splitLine: {
                                show: false
                            },
                            axisLabel: {
                                show: false
                            },
                            anchor: {
                                show: false
                            },
                            title: {
                                show: false
                            },
                            detail: {
                                valueAnimation: true,
                                width: '50%',
                                lineHeight: 30,
                                borderRadius: 8,
                                offsetCenter: [3.5, '-150%'],
                                fontSize: 12,
                                fontWeight: 'bolder',
                                formatter: (value) => {
                                    const result = (!isNaN(value) && !isNaN(parseFloat(value))) ? value : '00';
                                    return `${result} %`;
                                },
                                color: 'inherit'
                            },
                            data: [
                                {
                                    value: this.data.porcentaje.toFixed(2)
                                }
                            ]
                        }
                    ]
                };
                break;

            case 'actividades':
                if (this.data.length) {
                    let legendSelected = {};
                    const buildSeries = () => {
                        const keys = Object.keys(this.data[0].series);
                        const totals = {};
                        keys.forEach(k => {
                            let total = this.data.reduce((sum, item) => sum + item.series[k], 0);
                            legendSelected[k.toUpperCase()] = total ? true : false;
                            totals[k] = total;
                        });

                        return keys.map(key => ({
                            name: key.toUpperCase(),
                            type: "bar",
                            barGap: 0,
                            label: {
                                show: false,
                                position: "top",
                                distance: 4,
                                align: "center",
                                fontSize: 10,
                                color: "#fff",
                                formatter: (params) => {
                                    const value = params.value;
                                    return (value == 0) ? '' : value;
                                }
                            },
                            barMaxWidth: 30,
                            itemStyle: {
                                borderRadius: [5, 5, 0, 0] // arriba-izq, arriba-der, abajo-der, abajo-izq
                            },
                            data: this.data.map(item => ({
                                value: item.series[key],
                                text: item.text,
                                data: item
                            }))
                        }));
                    }

                    option = {
                        legend: {
                            show: false,
                            selected: legendSelected
                        },
                        tooltip: {
                            trigger: "axis",
                            axisPointer: {
                                type: "shadow"
                            },
                            extraCssText: `padding: 0 !important; background: transparent !important;`,
                            formatter: (params) => {
                                let result = `
                                <div class="card bg-white" style="width: 14rem; font-size:.725rem; margin:0; color: #86898eff;">
                                    <div class="card-body" style="padding:.5rem;">
                                        <strong>
                                            <i class="${params[0].data.data.transporte}"></i> ${params[0].data.text}
                                        </strong>`;

                                params.forEach(item => {
                                    const value = item.data.value;
                                    const data = item.data.data;
                                    result += `
                                    <div style="font-size:.7rem; margin:0px; padding:0;">
                                        ${item.marker} <span>${item.seriesName}</span>: <b>${value}</b>
                                    </div>`;

                                    if (item.seriesName === "INCIDENCIAS") {
                                        result += `
                                        <ul style="font-size:.7rem; margin:0px 0 0 32px; padding:0;">
                                            <li style="margin:0px; padding:0;">N1 - REMOTO: ${data.niveles.n1}</li>
                                            <li style="margin:0px; padding:0;">N2 - PRESENCIAL: ${data.niveles.n2}</li>
                                        </ul>`;
                                    }
                                });
                                result += `</div></div>`;

                                return result;
                            }
                            ,
                        },
                        grid: [
                            {
                                left: '5%',
                                right: '4%',
                                top: '5%',
                                bottom: '20%'
                            }
                        ],
                        xAxis: [
                            {
                                type: "category",
                                axisTick: {
                                    show: false
                                },
                                data: this.data.map(item => item.name),
                                axisLabel: {
                                    interval: 0,
                                    rotate: 30,
                                    textStyle: {
                                        // color: "#fff",
                                        fontSize: 10.5,
                                        fontWeight: "bold"
                                    }
                                }
                            }
                        ],
                        yAxis: [
                            {
                                type: "value",
                                axisLine: {
                                    lineStyle: {
                                        color: "#757575"
                                    }
                                },
                                splitLine: {
                                    lineStyle: {
                                        color: "#757575",
                                        width: 1,
                                        type: "dotted"
                                    }
                                }
                            }
                        ],
                        series: buildSeries()
                    };
                } else {
                    option = this.buildOptionSinData();
                }
                break;

            case 'incidencia_fechas':
                if (this.data.length) {
                    option = {
                        title: {
                            text: 'Total de incidencias por fecha',
                            // left: 'center',
                            textStyle: {
                                color: '#9fa6b2',
                                fontFamily: 'Arial, sans-serif',
                                fontSize: 14,
                            }

                        },
                        tooltip: {
                            trigger: 'axis',
                            formatter: function (params) {
                                const p = params[0];
                                return `${p.axisValue} : ${p.data}`;
                            },
                            axisPointer: {
                                animation: false
                            }
                        },
                        grid: {
                            left: '10%',
                            right: '7%',
                            top: '15%',
                            bottom: '20%'
                        },
                        xAxis: {
                            type: 'category', // 👈 seguimos con category
                            data: this.data.map(item => item.name),
                            axisLabel: {
                                fontSize: 9,
                                showMaxLabel: true,
                                showMinLabel: true
                            }
                        },
                        yAxis: {
                            type: 'value',
                            min: 'dataMin',
                            splitLine: {
                                lineStyle: {
                                    color: '#757575',
                                    width: 1,
                                    type: 'dotted'
                                }
                            }
                        },
                        dataZoom: [
                            {
                                type: 'inside'
                            },
                            {
                                type: 'slider',
                                top: '88%',
                                left: '19%',
                                right: '19%',
                                textStyle: {
                                    fontSize: 10,
                                },
                            }
                        ],
                        series: [
                            {
                                type: 'line',
                                smooth: true,
                                symbolSize: 0,
                                color: '#3b71ca',
                                areaStyle: {
                                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                        { offset: 0, color: 'rgba(59, 113, 202, .9)' }, // arriba (intenso)
                                        { offset: 1, color: 'rgba(59, 113, 202, .0)' }  // abajo (transparente)
                                    ])
                                },
                                data: this.data.map(item => item.total) // 👈 solo valores
                            }
                        ]
                    };
                } else {
                    option = this.buildOptionSinData('Total de incidencias por fecha');
                }
                break;

            case 'niveles':
                var total = this.data.total;
                var value = this.data.value;
                this.data.porcentaje = (value / total) * 100;

                option = {
                    tooltip: {
                        show: false
                    },
                    series: [
                        {
                            name: 'Pressure',
                            type: 'gauge',
                            startAngle: 225,
                            endAngle: -135,
                            min: 0,
                            max: 100,
                            itemStyle: {
                                color: this.config.color,
                                type: "dotted"
                            },
                            pointer: {
                                show: false
                            },
                            axisLine: {
                                lineStyle: {
                                    width: 25,
                                    color: [
                                        [1, '#0000000c'] // Dark: '#4e4e4eff' | Light: '#ebebebff'
                                    ]
                                }
                            },
                            progress: {
                                show: true,
                                width: 25,
                                roundCap: true,
                            },
                            axisTick: {
                                show: false
                            },
                            splitLine: {
                                show: false
                            },
                            axisLabel: {
                                show: false
                            },
                            detail: {
                                valueAnimation: true,
                                width: '60%',
                                offsetCenter: [0, 0],
                                fontSize: 20,
                                fontWeight: 'bolder',
                                formatter: (value) => {
                                    const result = (!isNaN(value) && !isNaN(parseFloat(value))) ? value : '00';
                                    return `${result} %`;
                                },
                                color: 'inherit'
                            },
                            data: [
                                {
                                    value: this.data.porcentaje.toFixed(2),
                                    // name: 'Porcentaje Unificado'
                                }
                            ]
                        }
                    ]
                };
                break;

            case 'problemas':
                if (this.data.length) {
                    var key = Object.keys(this.data[0].series);
                    this.data.sort((a, b) => {
                        const aVal = a.series[key[0]];
                        const bVal = b.series[key[0]];
                        return this.config.order == 'asc' ? bVal - aVal : aVal - bVal;
                    });

                    option = {
                        title: {
                            text: 'Ranking de problemas con mayor número de incidencias',
                            // left: 'center',
                            textStyle: {
                                color: '#9fa6b2',
                                fontFamily: 'Arial, sans-serif',
                                fontSize: 14,
                            }

                        },
                        tooltip: {
                            trigger: "axis",
                            axisPointer: {
                                type: "shadow"
                            },
                            formatter: (params) => {
                                let result = `<strong style="font-size:.725rem;">${params[0].data.text}</strong><br>`;
                                params.forEach(item => {
                                    const value = item.data.value;
                                    result += `${item.marker} <span style="font-size:.7rem;">Total</span>: <b>${value}</b><br/>`;
                                });
                                return result;
                            }
                        },
                        legend: {
                            show: false,
                        }
                        ,
                        grid: [
                            {
                                left: '12%',
                                right: '12%',
                                top: '15%',
                                bottom: '10%'
                            }
                        ],
                        xAxis: [
                            {
                                type: "value",
                                axisLine: {
                                    lineStyle: {
                                        color: "#757575"
                                    }
                                },
                                splitLine: {
                                    lineStyle: {
                                        color: "#757575",
                                        width: 1,
                                        type: "dotted"
                                    }
                                }
                            }
                        ],
                        yAxis: [
                            {
                                type: "category",
                                axisTick: {
                                    show: false
                                },
                                data: this.data.map(item => item.name),
                                axisLabel: {
                                    interval: 0,
                                    textStyle: {
                                        fontSize: 10.5,
                                        fontWeight: "bold"
                                    }
                                }
                            }
                        ],
                        series: {
                            name: key[0].toUpperCase(),
                            type: "bar",
                            barGap: 0,
                            label: {
                                show: false,
                            },
                            barMaxWidth: 30,
                            itemStyle: {
                                borderRadius: [0, 5, 5, 0] // arriba-izq, arriba-der, abajo-der, abajo-izq
                            },
                            label: {
                                show: true,
                                position: 'right',
                                valueAnimation: true,
                                color: '#948f8f'
                            },
                            data: this.data.map(item => ({
                                value: item.series[key[0]],
                                text: item.text,
                                itemStyle: {
                                    color: item.tipo_soporte == 1 ? '#3498db' : '#7367f0' // 👈 aquí usas el color del dato
                                }
                            }))
                        }
                    };
                } else {
                    option = this.buildOptionSinData('Ranking de problemas con mayor número de incidencias');
                }
                break;

            case 'subproblemas':
                if (this.data.length) {
                    var key = Object.keys(this.data[0].series);
                    this.data.sort((a, b) => {
                        const aVal = a.series[key[0]];
                        const bVal = b.series[key[0]];
                        return this.config.order == 'asc' ? bVal - aVal : aVal - bVal;
                    });

                    option = {
                        title: {
                            text: 'Ranking de subproblemas del problema seleccionado',
                            // left: 'center',
                            textStyle: {
                                color: '#9fa6b2',
                                fontFamily: 'Arial, sans-serif',
                                fontSize: 14,
                            }

                        },
                        tooltip: {
                            trigger: "axis",
                            axisPointer: {
                                type: "shadow"
                            },
                            formatter: (params) => {
                                let result = `<strong style="font-size:.725rem;">${params[0].data.text}</strong><br>`;
                                params.forEach(item => {
                                    const value = item.data.value;
                                    result += `${item.marker} <span style="font-size:.7rem;">Total</span>: <b>${value}</b><br/>`;
                                });
                                return result;
                            }
                        },
                        legend: {
                            show: false,
                        }
                        ,
                        grid: [
                            {
                                left: '30%',
                                right: '12%',
                                top: '15%',
                                bottom: '10%'
                            }
                        ],
                        xAxis: [
                            {
                                type: "value",
                                axisLine: {
                                    lineStyle: {
                                        color: "#757575"
                                    }
                                },
                                splitLine: {
                                    lineStyle: {
                                        color: "#757575",
                                        width: 1,
                                        type: "dotted"
                                    }
                                }
                            }
                        ],
                        yAxis: [
                            {
                                type: "category",
                                axisTick: {
                                    show: false
                                },
                                data: this.data.map(item => item.name),
                                axisLabel: {
                                    interval: 0,
                                    textStyle: {
                                        fontSize: 10.5,
                                        fontWeight: "bold"
                                    }
                                }
                            }
                        ],
                        series: {
                            name: key[0].toUpperCase(),
                            type: "bar",
                            barGap: 0,
                            label: {
                                show: true,
                                position: 'right',
                                valueAnimation: true,
                                color: '#948f8f'
                            },
                            barMaxWidth: 30,
                            itemStyle: {
                                borderRadius: [0, 5, 5, 0] // arriba-izq, arriba-der, abajo-der, abajo-izq
                            },
                            data: this.data.map(item => ({
                                value: item.series[key[0]],
                                text: item.text,
                                itemStyle: {
                                    color: '#e4a11b' // 👈 aquí usas el color del dato
                                }
                            }))
                        }
                    };
                } else {
                    option = this.buildOptionSinData('Ranking de subproblemas del problema seleccionado');
                }
                break;
        }

        return option;
    }

    buildOptionSinData(title = "") {
        const titutlo = () => {
            return title ? {
                title: {
                    text: title,
                    textStyle: {
                        color: '#9fa6b2',
                        fontFamily: 'Arial, sans-serif',
                        fontSize: 14,
                    }
                }
            } : {}
        }
        return {
            ...titutlo(),
            tooltip: {},
            xAxis: {
                type: 'category',
                data: []
            },
            yAxis: {
                type: 'value'
            },
            series: [{
                name: 'Ventas',
                type: 'bar',
                data: []
            }],
            graphic: [
                {
                    type: 'text',
                    left: 'center',
                    top: 'middle',
                    style: {
                        text: 'No hay datos disponibles',
                        fontSize: 20,
                        fill: '#999'
                    }
                }
            ]
        }
    }

    updateOption(params = {}) {
        const deepMerge = (target, source) => {
            for (let key in source) {
                const sourceVal = source[key];
                const targetVal = target[key];

                // si es un objeto no nulo y no es array → mergea
                if (
                    sourceVal &&
                    typeof sourceVal === "object" &&
                    !Array.isArray(sourceVal) &&
                    typeof targetVal === "object" &&
                    !Array.isArray(targetVal)
                ) {
                    deepMerge(targetVal, sourceVal);
                } else {
                    // en cualquier otro caso (null, "", [], número, string, etc.)
                    target[key] = sourceVal;
                }
            }
            return target;
        };
        deepMerge(this, params);

        if (this.chart) {
            this.chart.setOption(this._buildOptionEstado(), true);
        }
    }

    resize() {
        this.chart.resize();
    }

    resizeGraphic(size) {
        this.sizeGraphic = size;
        const ancho = this.chart.getWidth();
        const estadoAnterior = this.estadoAnteriorMap;

        if (estadoAnterior === undefined) return;

        if (ancho < this.sizeGraphic && estadoAnterior !== true) {
            this.chart.setOption(this._buildOptionEstado(), true);
            this.estadoAnteriorMap = true;
        } else if (ancho >= this.sizeGraphic && estadoAnterior !== false) {
            this.chart.setOption(this._buildOptionEstado(), true);
            this.estadoAnteriorMap = false;
        }
    }
}