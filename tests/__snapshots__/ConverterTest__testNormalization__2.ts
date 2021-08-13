export type CloudNotify = {
  id: string;
  fcmToken: string;
};

export type FullName = {
  firstName: string;
  lastName: string;
};

export enum NumberEnum {
  VIEW = 0,
  EDIT = 1,
  CREATE = 2,
}

export type Profile = {
  name: FullName | null;
  age: number;
};

export enum StringEnum {
  VIEW = 'view',
  EDIT = 'edit',
}

export type TestCreateDto = {
  age: number;
  name: string | null;
  latitude: number;
  longitude: number;
  achievements: any[];
  mixed: any;
  isApproved: boolean | null;
};

export type UserCreate = {
  id: string;
  profile: Profile | null;
};

