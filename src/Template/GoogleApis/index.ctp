<?php if ($error) : ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
<?php if ($isAuthorized) : ?>
<?= sprintf("You're logging in as %s ", $userInfo['name']) ?>
<?= $userInfo['email'] ? sprintf("(%s) ", $userInfo['email']) : "" ?>
<a href="<?= $this->Url->build(["controller" => "GoogleApis","action" => "revoke"]) ?>" class="btn btn-default btn-md" role="button">Logout</a>
<?php else: ?>
<?= sprintf("You need to authorize your google account to use Google API ") ?>
<a href="<?= $authUrl ?>" class="btn btn-default btn-md" role="button">Authenticate</a>
<?php endif; ?>
<br/>

<?php if ($isAuthorized) : ?>


<?= $this->Form->create(false, (['url' => ['controller' => 'GoogleApis', 'action' => 'index'], 'class'=>'' ])) ?>
<div class="row">
    <div class="col-sm-6 col-md-6">
        <?= $this->Form->input('siteUrl', ['class'=>'form-control', 'label' => ['text' => 'Site Url', 'class' => 'control-label'], 'type' => 'url', 'default' => isset($params['siteUrl']) ? $params['siteUrl'] : '', 'required' => true, 'placeholder' => 'http://example.com/']) ?>
    </div>
    <div class="col-sm-6 col-md-6">
        <div class="row">
            <label class="col-sm-12 col-md-12 control-label">Dimension Filter Groups</label>
        </div>
        <div class="row col-sm-12 col-md-12">
            <input type="button" class="btn btn-primary btn-md" value="Add Condition" onclick="searchform('add');"/>
            <input type="button" class="btn btn-warning btn-md" value="Clear All" onclick="searchform('remove');"/>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-2 col-md-2">
        <?= $this->Form->input('startDate', ['class'=>'form-control', 'label' => ['text' => 'Start Date', 'class' => 'control-label'], 'default' => isset($params['startDate']) ? $params['startDate'] : '', 'required' => true, 'placeholder' => 'YYYY-MM-DD']) ?>
        <?= $this->Form->input('endDate', ['class'=>'form-control', 'label' => ['text' => 'End Date', 'class' => 'control-label'], 'default' => isset($params['endDate']) ? $params['endDate'] : '', 'required' => true, 'placeholder' => 'YYYY-MM-DD']) ?>
        <?= $this->Form->input('rowLimit', ['class'=>'form-control', 'label' => ['text' => 'Row Limit', 'class' => 'control-label'], 'type' => 'number', 'default' => isset($params['rowLimit']) ? $params['rowLimit'] : '', 'placeholder' => '1000']) ?>
    </div>
    <div class="col-sm-2 col-md-2">
        <?= $this->Form->input('searchType', ['class'=>'form-control', 'label' => ['text' => 'Search Type', 'class' => 'control-label'], 'type' => 'select', 'options'=> $searchTypeOptions]) ?>
        <?= $this->Form->input('aggregationType', ['class'=>'form-control', 'label' => ['text' => 'Aggregation Type', 'class' => 'control-label'], 'type' => 'select', 'options'=> $aggregationTypeOptions]) ?>
        <?= $this->Form->input('startRow', ['class'=>'form-control', 'label' => ['text' => 'Start Row', 'class' => 'control-label'], 'type' => 'number', 'default' => isset($params['startRow']) ? $params['startRow'] : '', 'placeholder' => '0']) ?>
    </div>
    <div class="col-sm-2 col-md-2">
        <?= $this->Form->input('dimensions', ['class'=>'checkbox', 'label' => ['text' => 'Dimensions', 'class' => 'control-label'], 'type' => 'select', 'multiple' => 'checkbox', 'options' => $dimensionsOptions, 'val' => isset($params['dimensions']) ? $params['dimensions'] : [0]]) ?>
    </div>
    <div class="col-sm-6 col-md-6">
        <div class="row">
            <label class="col-sm-4 col-md-4 control-label">Dimension</label>
            <label class="col-sm-4 col-md-4 control-label">Operator</label>
            <label class="col-sm-4 col-md-4 control-label">Expression</label>
        </div>
        <div id="searchform">
            <?php if (isset($params['dimensionFilterGroups']) && $params['dimensionFilterGroups']['filters'] && $params['dimensionFilterGroups']['filters']['dimension']) : ?>
                <?php for ($i = 0; $i < count($params['dimensionFilterGroups']['filters']['dimension']); $i++) : ?>
                    <div class="row" style="padding-bottom:10px">
                        <div class="col-sm-4 col-md-4">
                        <?= $this->Form->input('dimensionFilterGroups.filters.dimension[]', ['class'=>'form-control', 'label' => false, 'type' => 'select', 'options'=> $dimensionsOptions, 'val' => $params['dimensionFilterGroups']['filters']['dimension'][$i]]) ?>
                        </div>
                        <div class="col-sm-4 col-md-4">
                        <?= $this->Form->input('dimensionFilterGroups.filters.operator[]', ['class'=>'form-control', 'label' => false, 'type' => 'select', 'options'=> $operatorOptions, 'val' => $params['dimensionFilterGroups']['filters']['operator'][$i]]) ?>
                        </div>
                        <div class="col-sm-4 col-md-4">
                        <?= $this->Form->input('dimensionFilterGroups.filters.expression[]', ['class'=>'form-control', 'label' => false, 'value' => $params['dimensionFilterGroups']['filters']['expression'][$i]]) ?>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-10 col-md-10" style="padding-top: 20px">
        <?= $this->Form->button(__('Submit'), ['name' => 'Submit', 'class' => 'btn btn-primary btn-md']) ?>
        <a href="<?= $this->Url->build(["controller" => "GoogleApis","action" => "clear_search_params"]) ?>" class="btn btn-warning btn-md" role="button">Clear Input Params</a>
    </div>
</div>
<?= $this->Form->end() ?>

<?php if ($results) : ?>
<div class="h-divider">
<div class="table-responsive col-sm-11 col-md-11">
<table class="table table-hover">
    <thead>
        <tr>
            <th>keys</th>
            <th>clicks</th>
            <th>impressions</th>
            <th>ctr</th>
            <th>position</th>
        </tr>
    </thead>
    <?php foreach ($results as $result) : ?>
    <tbody>
        <tr>
            <td>
            <?php if ($result['keys']) : ?>
                <?php foreach ($result['keys'] as $key) : ?>
                    <?= $key ?><br/>
                <?php endforeach; ?>
            <?php endif; ?>
            </td>
            <td><?= $result['clicks'] ?></td>
            <td><?= $result['impressions'] ?></td>
            <td><?= $result['ctr'] ?></td>
            <td><?= $result['position'] ?></td>
        <tr/>
    </tbody>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>
<?php endif; ?>

<script type="text/javascript">
    var index = 0;
    var searchform = function(name) {
        this.method = {
            remove: function() {
                index = 0;
                $('#searchform').html("");
            },
            add: function() {
                /*
                var data = {options : {}, index : index};
                $(".searchlogic_options").each(function() {
                    data.options[$(this).attr("name")] = $(this).val();
                })
                */
                $.ajax({
                    url: "<?= $this->Url->build(["controller" => "GoogleApis","action" => "searchForm"]) ?>",
                    data: {},
                    type: 'GET',
                    success: function(msg) {
                        //index++;
                        $('#searchform').append($(msg))
                    }
                });
            }}
        this.method[name]();
    }
</script>