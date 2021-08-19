export type FullName = {
  firstName: string;
  lastName: string;
};

export type Profile = {
  name: FullName | null | string;
  age: number;
};

export type UserCreate = {
  id: string;
  profile: Profile | null;
};

