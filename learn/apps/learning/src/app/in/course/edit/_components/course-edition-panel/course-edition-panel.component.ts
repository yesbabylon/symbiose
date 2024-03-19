import { Component, OnInit } from '@angular/core';
import { Chapter, Module } from '../../../../../_types/learn';
import { Router } from '@angular/router';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { MatTreeFlatDataSource, MatTreeFlattener } from '@angular/material/tree';
import { FlatTreeControl } from '@angular/cdk/tree';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { SelectionModel } from '@angular/cdk/collections';
import { CdkDragDrop } from '@angular/cdk/drag-drop';
import { MatSnackBar } from '@angular/material/snack-bar';

interface TreeNode {
    id: number;
    order: number;
    title: string;
    lessons?: TreeNode[];
}

export class FlatNode {
    constructor(
        public expandable: boolean,
        public order: number,
        public title: string,
        public level: number,
        public id: number
    ) {}
}

@Component({
    selector: 'app-course-edition-panel',
    templateUrl: './course-edition-panel.component.html',
    styleUrls: ['./course-edition-panel.component.scss'],
})
export class CourseEditionPanelComponent implements OnInit {
    public modules: Module[] & TreeNode[] = [];

    treeControl: FlatTreeControl<any>;
    treeFlattener: MatTreeFlattener<TreeNode, FlatNode>;
    dataSource: MatTreeFlatDataSource<TreeNode, FlatNode>;
    expansionModel = new SelectionModel<any>(true);
    dataChange: BehaviorSubject<any[]> = new BehaviorSubject<any[]>([]);

    constructor(
        private router: Router,
        private api: ApiService,
        private matSnackBar: MatSnackBar
    ) {
        this.treeFlattener = new MatTreeFlattener(this.transformer, this.getLevel, this.isExpandable, this.getChildren);
        this.treeControl = new FlatTreeControl<FlatNode>(this.getLevel, this.isExpandable);
        this.dataSource = new MatTreeFlatDataSource(this.treeControl, this.treeFlattener);

        this.dataChange.subscribe(data => this.rebuildTreeForData(data));
    }

    public transformer = (node: TreeNode, level: number) =>
        new FlatNode(!!node.lessons, node.order, node.title, level, node.id);
    public getLevel = (node: FlatNode) => node.level;
    public isExpandable = (node: FlatNode) => node.expandable;
    public getChildren = (node: TreeNode): Observable<any> => of(node.lessons);

    public hasChild = (_: number, node: FlatNode) => node.expandable;

    public ngOnInit(): void {
        this.getModulesRessources();
    }

    public onDrop(event: CdkDragDrop<string[]>): void {
        if (!event.isPointerOverContainer) return;

        const modules = this.modules;
        const draggedNodeIndex: number = event.previousIndex;
        const draggedNode: FlatNode = this.dataSource._expandedData.value[draggedNodeIndex];
        const droppedNodeIndex: number = event.currentIndex;
        let droppedNode: FlatNode = this.dataSource._expandedData.value[droppedNodeIndex];

        // Prevent the dragged node lvl 1 to have a dropped node level 0 if dropped at the first position
        if (draggedNode.level === 1 && droppedNode.level === 0 && draggedNodeIndex > droppedNodeIndex) {
            droppedNode = this.dataSource._expandedData.value[droppedNodeIndex - 1];
        }

        // level 1
        if (draggedNode.level === 1) {
            let draggedLesson: Chapter | undefined;
            let draggedLessonIndex: number | undefined;
            let moduleOfDraggedLesson: Module | undefined;
            let droppedLesson: Chapter | undefined;
            let droppedLessonIndex: number | undefined;
            let moduleOfDroppedLesson: Module | undefined;

            const moduleLessonsNodeLength = modules.find((module: Module) => module.id === droppedNode.id)?.lessons
                ?.length;

            if (moduleLessonsNodeLength === 0) {
                const droppedModule = modules.find((module: Module) => module.id === droppedNode.id);
                let draggedLesson: Chapter | undefined;
                let moduleIdOfLesson: number | undefined;

                modules.forEach((module: Module) => {
                    if (module.lessons && module.lessons.find((lesson: Chapter) => lesson.id === draggedNode.id)) {
                        draggedLesson = module.lessons.find((lesson: Chapter) => lesson.id === draggedNode.id);
                        const draggedLessonIndex = module.lessons.findIndex(
                            (lesson: Chapter) => lesson.id === draggedNode.id
                        );
                        module.lessons.splice(draggedLessonIndex, 1);
                        moduleIdOfLesson = module.id;
                    }
                });

                if (draggedLesson && droppedModule) {
                    draggedLesson.order = 1;
                    draggedLesson.module_id = moduleIdOfLesson;
                    modules.forEach((module: Module): void => {
                        if (module.id === droppedModule.id) {
                            module.lessons!.push(draggedLesson!);
                        }
                    });
                    this.updateEntityOrder('Chapter', draggedLesson);
                }

                this.modules = modules;
                this.dataChange.next(modules);
                return;
            }

            // 1. find the lesson of the draggedNode
            for (let module of modules) {
                if (module.lessons) {
                    draggedLesson = module.lessons.find((lesson: Chapter) => lesson.id === draggedNode.id);
                    if (draggedLesson) {
                        moduleOfDraggedLesson = module;
                        draggedLessonIndex = module.lessons.findIndex(
                            (lesson: Chapter) => lesson.id === draggedNode.id
                        );
                        break;
                    }
                }
            }

            // 2. find the lesson of the droppedNode
            for (let module of modules) {
                if (module.lessons) {
                    droppedLesson = module.lessons.find((lesson: Chapter) => lesson.id === droppedNode.id);
                    if (droppedLesson) {
                        moduleOfDroppedLesson = module;
                        droppedLessonIndex = module.lessons.findIndex(
                            (lesson: Chapter) => lesson.id === droppedNode.id
                        );
                        break;
                    }
                }
            }

            // reduce condition for indepth condition
            if (
                moduleOfDraggedLesson &&
                typeof draggedLessonIndex === 'number' &&
                draggedLessonIndex > -1 &&
                typeof droppedLessonIndex === 'number' &&
                droppedLessonIndex > -1 &&
                draggedLesson
            ) {
                // 3. if both modules are the same, sort the lessons
                if (moduleOfDraggedLesson === moduleOfDroppedLesson) {
                    const lessons = moduleOfDraggedLesson.lessons;
                    if (lessons) {
                        // 3.1 remove draggedNode from module.lessons
                        lessons.splice(draggedLessonIndex, 1);

                        // 3.2 insert draggedNode at droppedNodeIndex
                        lessons.splice(droppedLessonIndex, 0, draggedLesson);

                        // 3.3 update order of lessons
                        lessons.forEach((lesson: Chapter, index: number) => {
                            lesson.order = index + 1;
                            this.updateEntityOrder('Chapter', lesson);
                        });

                        // 3.4 save changes
                        modules.forEach((module: Module) => {
                            if (moduleOfDraggedLesson && module.id === moduleOfDraggedLesson.id) {
                                module.lessons = lessons;
                            }
                        });
                    }
                }

                // 4. if both modules are different,
                else if (moduleOfDroppedLesson && moduleOfDraggedLesson !== moduleOfDroppedLesson) {
                    const lessonsOfDraggedModule = moduleOfDraggedLesson.lessons;
                    const lessonsOfDroppedModule = moduleOfDroppedLesson.lessons;

                    if (lessonsOfDraggedModule && lessonsOfDroppedModule) {
                        // 4.1 remove draggedNode from draggedModule.lessons
                        lessonsOfDraggedModule.splice(draggedLessonIndex, 1);

                        // 4.2 update order of lessons of the draggedLesson of the first module
                        lessonsOfDraggedModule.forEach((lesson: Chapter, index: number) => {
                            lesson.order = index + 1;
                            this.updateEntityOrder('Chapter', lesson);
                        });

                        // 4.3 save changes
                        modules.forEach((module: Module) => {
                            if (moduleOfDraggedLesson && module.id === moduleOfDraggedLesson.id) {
                                module.lessons = lessonsOfDraggedModule;
                            }
                        });

                        // 4.4 insert it at droppedNodeIndex of droppedModule.lessons
                        if (draggedNodeIndex < droppedNodeIndex) {
                            if (droppedLessonIndex === 0 && droppedNode.level === 0) {
                                lessonsOfDroppedModule.unshift(draggedLesson);
                            } else if (lessonsOfDroppedModule.length - 1 === droppedLessonIndex) {
                                lessonsOfDroppedModule.push(draggedLesson);
                            } else {
                                if (draggedNodeIndex < droppedNodeIndex) {
                                    droppedLessonIndex = droppedLessonIndex + 1;
                                }
                                lessonsOfDroppedModule.splice(droppedLessonIndex, 0, draggedLesson);
                            }
                        } else {
                            if (droppedLessonIndex === lessonsOfDroppedModule.length - 1) {
                                lessonsOfDroppedModule.push(draggedLesson);
                            } else {
                                lessonsOfDroppedModule.splice(droppedLessonIndex, 0, draggedLesson);
                            }
                        }

                        draggedLesson.module_id = moduleOfDroppedLesson.id;

                        // 4.5 update order of lessons of the droppedLesson of the second module
                        lessonsOfDroppedModule.forEach((lesson: Chapter, index: number) => {
                            lesson.order = index + 1;
                            this.updateEntityOrder('Chapter', lesson);
                        });

                        // 4.6 save changes locally
                        modules.forEach((module: Module) => {
                            if (moduleOfDroppedLesson && module.id === moduleOfDroppedLesson.id) {
                                module.lessons = lessonsOfDroppedModule;
                            }
                        });
                    }
                }
            }
        }
        // level 0
        else if (draggedNode.level === 0 && droppedNode.level === 0) {
            // sort this.modules
            const draggedModule = modules.find((module: Module) => module.id === draggedNode.id);
            const draggedModuleIndex = modules.findIndex((module: Module) => module.id === draggedNode.id);
            const droppedModule = modules.findIndex((module: Module) => module.id === droppedNode.id);

            if (draggedModule) {
                // remove draggedModule from modules
                modules.splice(draggedModuleIndex, 1);

                // insert draggedModule at droppedModuleIndex
                modules.splice(droppedModule, 0, draggedModule);

                // update order of modules
                modules.forEach((module: Module, index: number) => {
                    module.order = index + 1;
                    this.updateEntityOrder('Module', module);
                });
            }
        }
        this.modules = modules;
        this.dataChange.next(modules);
    }

    /**
     * The following methods are for persisting the tree expand state
     * after being rebuilt
     */
    public rebuildTreeForData(data: any): void {
        this.dataSource.data = data;
        this.expansionModel.selected.forEach(id => {
            const node = this.treeControl.dataNodes.find(n => n.id === id);
            this.treeControl.expand(node);
        });
    }

    private async getModulesRessources(): Promise<void> {
        const urlSegments: string[] = this.router.url.split('/');
        const courseId: number = +urlSegments[urlSegments.length - 2];
        try {
            this.modules = await this.api.collect(
                'learn\\Module',
                ['course_id', '=', courseId],
                ['id', 'title', 'page_count', 'description', 'duration', 'order', 'chapter_count', 'course_id'],
                'order'
            );
            await this.getLessonsRessources();
            this.dataChange.next(this.modules);
        } catch (error) {
            console.error(error, 'ADD ORDER FIELD INSIDE MODULES Controller of QURSUS Package !!!');
        }
    }

    private async getLessonsRessources(): Promise<void> {
        try {
            for (const module of this.modules) {
                await this.api.collect(
                    'learn\\Chapter',
                    ['module_id', '=', module.id],
                    ['id', 'title', 'order'],
                    'order'
                );
            }
        } catch (error) {
            console.error(error);
        }
    }

    private updateEntityOrder(name: 'Module' | 'Chapter', entity: Module | Chapter): void {
        const model: { [key: string]: any } = { order: entity.order };

        if (name === 'Chapter' && 'module_id' in entity) {
            model.module_id = entity.module_id;
        }

        const entityName = name === 'Module' ? 'module' : 'lesson';
        try {
            this.api.update(`learn\\${name}`, [entity.id], model);

            this.matSnackBar.open(`The ${entityName} has been successfully moved.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        } catch (error) {
            console.error(error);

            this.matSnackBar.open(`An error occurred while updating the ${entityName}.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        }
    }
}
