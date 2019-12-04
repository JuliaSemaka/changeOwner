<html>
<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
          integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="css/style2.css" rel="stylesheet">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="//api.bitrix24.com/api/v1/"></script>
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-sm department">

        </div>
<!--        <div class="col-sm position">-->
<!---->
<!--        </div>-->
        <div class="col-sm user">

        </div>
    </div>
</div>

</body>
</html>

<script>

    $(document).ready(function () {
        // var arrayKey = [5]; //с какого отдела сотрудники (отдел маркетинга и отдел продаж) ??
        // var leadId = '10';
        var leadId = "<?php echo json_decode($_REQUEST['PLACEMENT_OPTIONS'])->ID ?>";

        BX24.init(function () {
            app.addPlacement();
            app.displayDepartment();

            $('.department').on('click', '.btn-department', function () {
                app.displayUsers($(this).attr("data_department_id"));
                $('.user').children().remove();
                $('.department').children().not(this).removeClass('btn-success').addClass('btn-outline-dark');
                $(this).removeClass('btn-outline-dark').addClass('btn-success');
            });

            // app.displayUsers(arrayKey);

            $('.user').on('click', '.btn-user', function () {
                app.clickOnUser($(this).attr("data_user_id"), leadId);

                $('.btn-user').attr('disabled', true);

                $(this).removeClass('btn-outline-dark');
                $(this).addClass('btn-success');

            });
        });
    });


    function application() {
    }

    application.prototype.addPlacement = function () {
        var isPlacement = true;

        BX24.callMethod(
            'placement.get',
            {},
            function (res) {
                if (res.error()) {
                    console.error(res.error());
                } else {
                    // console.log(res.data().length);
                    if (res.data().length > 0) {
                        $.each(res.data(), function (index, value) {
                            if (value.placement === "CRM_LEAD_DETAIL_TAB" && value.handler === "https://ats3.demo-zone.itach.by/bitrix24/application/") {
                                isPlacement = false;
                            }
                        });
                    }

                    if (isPlacement) {
                        BX24.callMethod(
                            'placement.bind',
                            {
                                'PLACEMENT': 'CRM_LEAD_DETAIL_TAB',
                                'HANDLER': 'https://ats3.demo-zone.itach.by/bitrix24/application/',
                                'TITLE': 'Выбор ответственного'
                            }
                        );
                    }
                }
            });
    }

    application.prototype.displayDepartment = function () {
        BX24.callMethod(
            'department.get',
            {
                "order": "ASC"
            },
            function (result) {
                $.each(result.data(), function (index1, value1) {
                    $(".department").append('<button data_department_id="' + value1.ID + '" type="button" class="btn btn-outline-dark btn-department">' + value1.NAME + '</button><br>');
                });
            }
        );
    }

    // application.prototype.displayPosition = function (departmentId) {
    //     console.log(departmentId);
    //     BX24.callMethod(
    //         'user.get',
    //         {
    //             'USER_TYPE': 'employee'
    //         },
    //         function (result) {
    //             console.log(result.data());
    //         });
    // }


        application.prototype.displayUsers = function (arrDepartment) {

            var arrUser = [];   // создаем массив сотрудников

            BX24.callMethod(
                'user.get',
                {
                    'USER_TYPE': 'employee',
                    'UF_DEPARTMENT': arrDepartment
                },
                function (result) {
                    console.log(result.data());

                    $.each(result.data(), function (index, value) {
                        arrUser.push(value.ID);  //заполняем сотрудниками
                    });

                    var filteredLeads = [];
                    // var i = 0;

                    var batch = new Batch();

                        param = {
                            filter: {
                                "ASSIGNED_BY_ID": arrUser, //положить массив
                                "STATUS_SEMANTIC_ID": "P",
                            },
                            order: {
                                "ID": "ASC"
                            },
                            start: 0,
                            select: ["ID", "NAME", "TITLE", "ASSIGNED_BY_ID", "DATE_CREATE"]
                        };

                    batch.getList("crm.lead.list", param, function (data) {
                    console.log(data);
                        var nowDate = new Date();

                        $.each(data, function (batchIndex, batchValue) {
                            $.each(batchValue, function (responseIndex, responseValue) {
                                filteredLeads.push(responseValue);
                            })
                        });
                        console.log(filteredLeads, result.data());

                        var today = new Date();
                        var yesterday = new Date(today.valueOf() - 24*60*60*1000);
                        var monthAgo = new Date(today.valueOf() - 30*24*60*60*1000);

                        $.each(result.data(), function (index1, value1) {
                            var colLeadsDays = 0;  //колличество лидов к каждому сотруднику за день
                            var colLeadsMonth = 0; //колличество лидов к каждому сотруднику за месяц
                            $.each(filteredLeads, function (index2, value2) {
                                var dateCreate = new Date(value2.DATE_CREATE);
                                if (value1.ID === value2.ASSIGNED_BY_ID) {

                                    if ( dateCreate > monthAgo ) {
                                        colLeadsMonth++;
                                    }
                                    if (dateCreate > yesterday) {
                                        colLeadsDays++;
                                    }
                                }
                            });

                            $(".user").append(
                                '<button data_user_id="' + value1.ID + '" class="btn btn-outline-dark btn-user"> ' +
                                '<div>(<span>' + value1.WORK_POSITION + '</span>) ' + value1.NAME + ' ' + value1.LAST_NAME+ '</div>' +
                                '<div>  За день: <span>' + colLeadsDays + '</span> , За месяц: <span>' + colLeadsMonth + '</span></div>' +
                                '</button><br>');
                        });
                    });

                    // BX24.callMethod("crm.lead.list",{
                    //     filter: {
                    //         "ASSIGNED_BY_ID": arrUser, //положить массив
                    //         "STATUS_SEMANTIC_ID": "P",
                    //     },
                    //     select: ["ID", "NAME", "TITLE", "ASSIGNED_BY_ID"]
                    // }, function (result) {
                    //     batchCount = Math.ceil(result.total%50);
                    // })
                    //
                    // batch.addToBatch(param);
                    // batch.combine(batch, function (data) {
                    //     console.log(data);
                    // })

                    // BX24.callMethod(   //достаём лиды по сотрудникам
                    //     "crm.lead.list",
                    //     ,
                    //     function (result2) {
                    //
                    //
                    //         if (result2.error()) {
                    //             console.error(result2.error());
                    //         } else {
                    //             for(var j = 0; j < result2.data().length; j++){
                    //                 filteredLeads.push(result2.data()[j]);
                    //             }
                    //             if (result2.more()){
                    //                 result2.next();
                    //             } else{
                    //                 $.each(result.data(), function (index1, value1) {
                    //                     var colLeads = 0;  //колличество лидов к каждому сотруднику
                    //                     $.each(filteredLeads, function (index2, value2) {
                    //                         if (value1.ID === value2.ASSIGNED_BY_ID) {
                    //                             colLeads++;
                    //                         }
                    //                     });
                    //
                    //                     $( ".user" ).append('<button data_user_id="'+ value1.ID +'" type="button" class="btn btn-outline-dark btn-user">' + value1.NAME + ' : ' + colLeads + '</button><br>' );
                    //                 });
                    //             }
                    //
                    //
                    //         }
                    //
                    //
                    //     }
                    // );
                }
            );

        }

        application.prototype.clickOnUser = function (userId, leadId) {

            BX24.callMethod(
                "crm.lead.update",
                {
                    id: leadId,
                    fields:
                        {
                            "ASSIGNED_BY_ID": userId,
                        }
                },
                function (result) {
                    if (result.error())
                        console.error(result.error());
                    else {
                        console.info(result.data());
                    }
                }
            );

        }

        app = new application();

        /**
         * Batch() - объект для создания пакетных запросов
         *       addToBatch(expr) - получает выражение с парамметрами и добавляет его в список запросов
         *       combine(batch, function()) - @batch:объект к которому вызываем batch , @function(): колбек функция для обработки запроса
         */
        function Batch() {
            this.batcher = {},
                this.counter = 0,
                this.result = [];
            this.addToBatch = function (expr) {
                this.batcher[this.counter++] = expr;
                return this.counter;
            },
                this.combine = function (frg, caller) {

                    BX24.callBatch(this.batcher, function (res) {
                        var result = [];

                        // console.log(res);
                        // console.log(result);
                        for (b in res) {

                            result.push(res[b].data());
                            if (res[b].error()) {
                                console.log(res[b].error());
                            }
                        }


                        console.log(result, "batch");

                        if (!!caller)
                            caller(result);
                    });

                },
                this.before = function () {
                    console.log(this.result, "lodsko");
                },
                this.next = function () {
                    if (this.counter < this.result.length) {
                        //console.log(this.result[0]);
                        return this.result[this.counter++];
                    } else {
                        return false;
                    }
                };
            this.getList = function (method, filter, call) {
                var method = method,
                    filter = filter || {};
                BX24.callMethod(method, filter, function (result) {

                    var total = Math.ceil(result.answer.total / 50),
                        batchList = new Batch();
                    for (var i = 0; i < total; i++) {
                        filter = JSON.parse(JSON.stringify(filter));
                        filter.start = i * 50;
                        batchList.addToBatch(["crm.lead.list", filter]);
                    }
                    batchList.combine(batchList, function (dataList) {
                        call(dataList);
                    })
                });
            }
        }
</script>