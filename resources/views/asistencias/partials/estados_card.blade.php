<div class="row" id="list-estado"></div>
<script>
    let bliColor = {
        info: '#54b4d3',
        warning: '#e4a11b',
        purple: '#7367f0',
        primary: '#3b71ca',
        success: '#14a44d',
        danger: '#dc4c64',
        light: '#fbfbfb',
        secondary: '#959595',
        dark: '#332d2d',
    };

    let incidencia_estados = [{
        name: "estado-total",
        text: "Total de Asistencias",
        color: "secondary",
        searchTable: 0,
        chart: false,
    },
    {
        name: "estado-asistencias",
        text: "Puntuales",
        color: "success",
        searchTable: 2,
        chart: true,
    },
    {
        name: "estado-faltas",
        text: "Faltas",
        color: "danger",
        searchTable: 1,
        chart: true,
    },
    {
        name: "estado-tardanzas",
        text: "Tardanzas",
        color: "warning",
        searchTable: 4,
        chart: true,
    },
    {
        name: "estado-justificados",
        text: "Justificados",
        color: "info",
        searchTable: 3,
        chart: true,
    },
    {
        name: "estado-derivados",
        text: "Derivados",
        color: "purple",
        searchTable: 7,
        chart: true,
    },
    ];

    let list_estado = $('#list-estado');
    incidencia_estados.forEach((e, i) => {
        list_estado.append(
            $('<div>', {
                class: 'col-xxl-2 col-4 mb-2'
            }).append(
                $('<div>', {
                    class: 'card',
                    style: 'height: 100%;',
                    type: 'button',
                    "data-mdb-ripple-init": '',
                    onclick: `searchTable(${e.searchTable})`
                }).append(
                    $('<div>', {
                        class: 'card-body row pe-2',
                        style: 'padding-left: .75rem;color: ' + bliColor[e.color],
                    }).append(
                        $('<div>', {
                            class: e.chart ? 'col-7' : ''
                        }).append(
                            $('<h6>', {
                                class: 'card-title chart-estado-title mb-1'
                            }).text(e.text),
                            $('<h4>', {
                                class: 'subtitle-count',
                                id: 'count-' + e.name
                            }).text(0)
                        ),
                        e.chart ? $('<div>', {
                            class: 'col-5 p-0'
                        }).append($('<div>', {
                            id: 'chart-' + e.name
                        })) : null
                    )
                )
            )
        );
        if (e.chart) {
            e.chart = new ChartMananger({
                id: 'chart-' + e.name,
                config: {
                    tipo: 'estado',
                    altura: 5,
                    bg: bliColor[e.color]
                },
                data: {
                    total: 100,
                    value: 0
                }
            });
        }
    });

    function setEstados(obj_estado, total) {
        $('#count-estado-total').text(total);

        obj_estado.forEach((e, i) => {
            $('#count-' + e.name).text(e.value);
            let estado = incidencia_estados.find(ie => ie.name == e.name);
            if (estado.chart)
                estado.chart.updateOption({
                    data: {
                        total: total,
                        value: e.value
                    }
                });
        });
    }
</script>