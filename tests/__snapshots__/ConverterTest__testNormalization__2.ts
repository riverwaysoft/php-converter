export type FullName = {
  firstName: string;
  lastName: string;
};

export enum NumberEnum {
  VIEW = 0,
  EDIT = 1,
  CREATE = 2,
}

export enum PermissionsEnum {
  VIEW = 'view',
  EDIT = 'edit',
}

export type Profile = {
  name: FullName | null;
  age: number;
};

export type UserCreate = {
  id: string;
  permissions: PermissionsEnum;
  profile: Profile | null;
  age: number;
  name: string | null;
  latitude: number;
  longitude: number;
  achievements: any[];
  mixed: any;
  isApproved: boolean | null;
};

