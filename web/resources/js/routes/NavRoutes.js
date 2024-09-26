import {
  ChartPieIcon,
  DocumentTextIcon,
  Square3Stack3DIcon,
  SquaresPlusIcon,
} from '@heroicons/vue/20/solid/index.js';
import { Routes } from './Routes.js';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import route from '../../../vendor/tightenco/ziggy/src/js/index.js';

/**
 * Single-point-of truth definition for which
 * routes to placed in navigation menus.
 * Array order is explicit.
 */
export const NavRoutes = [
  {
    icon: Square3Stack3DIcon,
    route: Routes.project,
  },
  {
    icon: SquaresPlusIcon,
    route: Routes.preparation,
  },
  {
    icon: DocumentTextIcon,
    route: Routes.coding,
  },
  {
    icon: ChartPieIcon,
    route: Routes.analysis,
  },
  {
    icon: UserCircleIcon,
    route: Routes.profile,
  },
];
