<?php
/**
 * Created by Maxim Omelchenko <omelchenko@samsonos.com>
 * on 31.03.2015 at 18:55
 */

namespace samsoncms\input\material;

class MaterialInputApplication extends \samsoncms\input\InputApplication
{
    protected $id = 'samson_cms_input_material';

    /**
     * Create field class instance
     *
     * @param string|\samson\activerecord\dbRecord $entity Class name or object
     * @param string|null $param $entity class field
     * @param int $identifier Identifier to find and create $entity instance
     * @param \samson\activerecord\dbQuery|null $dbQuery Database object
     * @return self
     */
    public function createField($entity, $param = null, $identifier = null, $dbQuery = null)
    {
        $this->field = new Material($entity, $param, $identifier, $dbQuery);
        return $this;
    }

    /**
     * @return array Asynchronous result array
     */
    public function __async_form()
    {
        /** @var array $table Result of asynchronous controller
         * Represented as array of rendered table and pager objects */
        $table = $this->__async_table(0);

        // If parent structure is not set, store structure by itself instead
        $parent = isset($parent) ? $parent : \samson\cms\web\navigation\CMSNav::fullTree();

        /** @var \samson\treeview\SamsonTree $tree Tree structure object */
        $tree = new \samson\treeview\SamsonTree('tree/template', 0, $this->id . '/getchild');

        /** @var string $treeHTML Rendered tree */
        $treeHTML = $tree->htmlTree($parent);

        // Return asynchronous result
        return array(
            'status' => 1,
            'html' => $this->view('form')
                ->set($table)
                ->set('tree', $treeHTML)
                ->output()
        );
    }

    /**
     * @param int $structureId Structure identifier to form table
     * @param string $search Search string
     * @param int $page Page number
     * @return array Asynchronous result array
     */
    public function __async_table($structureId, $search = null, $page = null)
    {
        /** @var array $response Asynchronous controller result */
        $response = array('status' => false);

        /** @var \samson\cms\Navigation $structure Object to store selected structure */
        $structure = null;

        // Try to find it in database
        dbQuery('\samson\cms\Navigation')->id($structureId)->first($structure);

        /** @var FieldMaterialTable $table Object to store set of materials */
        $table = new FieldMaterialTable($structure, $search, $page);

        /** @var string $tableHTML Rendered table */
        $tableHTML = $table->render();

        /** @var string $pager_html Rendered pager */
        $pagerHTML = $table->pager->toHTML();

        // Return table

        $response['status'] = true;
        $response['table_html'] = $tableHTML;
        $response['pager_html'] = $pagerHTML;

        return $response;
    }

    /**
     * Function to retrieve tree structure
     * @param int $structureId Current structure identifier
     * @return array Asynchronous result
     */
    public function __async_getchild($structureId)
    {
        /** @var \samson\cms\Navigation $structure Current structure object */
        $structure = null;

        // If structure was found by Identifier
        if (dbQuery('\samson\cms\Navigation')->cond('StructureID', $structureId)->first($structure)) {

            /** @var \samson\treeview\SamsonTree $tree Object to store tree structure */
            $tree = new \samson\treeview\SamsonTree('tree/template', 0, 'product/addchildren');

            // Asynchronous controller performed and JSON object is returned
            return array('status' => 1, 'tree' => $tree->htmlTree($structure));
        }

        // Asynchronous controller failed
        return array('status' => 0);
    }

    public function __async_confirm($materialId)
    {
        $name = null;
        if (dbQuery('material')->cond('MaterialID', $materialId)->fieldsNew('Name', $name)) {
            $name = $name[0];
            /** @var \samson\activerecord\materialfield $field Materialfield object to store material id */
            $this->createField($_GET['e'], $_GET['f'], $_GET['i']);
            $this->field->save($materialId);
            return array('status' => true, 'material' => $name);
        }
        return array('status' => false);
    }

    public function __async_delete()
    {
        /** @var \samson\activerecord\materialfield $field Materialfield object to store material id */
        $this->createField($_GET['e'], $_GET['f'], $_GET['i']);
        $this->field->save('');
        return array('status'=>true);
    }

    /** @see \samson\core\iModuleViewable::toView() */
    public function toView($prefix = null, array $restricted = array())
    {
        /** @var \samson\activerecord\material $material Additional field material */
        $material = null;
        $params = $this->field->getObjectData();

        $this->view($this->defaultView)
            ->set('deleteController', url_build($this->id, 'delete'))
            ->set('getParams', '?f=' . $params['param'] . '&e=' . $params['entity'] . '&i=' . $params['dbObject']->id);

        if ((int)$params['value'] != 0) {
            // If material exists
            if (!dbQuery('material')->cond('MaterialID', $params['value'])->first($material)) {
                $this->set('material_Name', t('Данный материал не существует! Выберите новый!', true));
            } else {
                $this->set($material, 'material');
            }
        }

        // Return input fields collection prepared for module view
        return array($prefix.'html' => $this->output());
    }
}
