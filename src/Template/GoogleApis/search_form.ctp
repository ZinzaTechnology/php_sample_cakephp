<div class="row" style="padding-bottom:10px">
    <div class="col-sm-4 col-md-4">
    <?= $this->Form->input('dimensionFilterGroups.filters.dimension[]', ['class'=>'form-control', 'label' => false, 'type' => 'select', 'options'=> $dimensionsOptions]) ?>
    </div>
    <div class="col-sm-4 col-md-4">
    <?= $this->Form->input('dimensionFilterGroups.filters.operator[]', ['class'=>'form-control', 'label' => false, 'type' => 'select', 'options'=> $operatorOptions]) ?>
    </div>
    <div class="col-sm-4 col-md-4">
    <?= $this->Form->input('dimensionFilterGroups.filters.expression[]', ['class'=>'form-control', 'label' => false]) ?>
    </div>
</div>